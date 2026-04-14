<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IneRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        $token = $user->createToken('quasar_app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'parent_id' => $user->parent_id
            ]
        ]);
    }

    /**
     * Recibe un array de registros locales, calcula coordenadas con Google y los guarda.
     */
    public function sync(Request $request)
    {
        // Evitamos el timeout si llegan muchos registros de golpe
        set_time_limit(0);

        // Validamos solo la estructura principal
        $request->validate([
            'records' => 'required|array',
        ]);

        $syncedLocalIds = [];
        $errors = [];

        foreach ($request->records as $index => $recordData) {
            try {
                // 1. VALIDACIÓN INDIVIDUAL (Para no romper el lote completo)
                $validator = Validator::make($recordData, [
                    'clave_elector' => 'required|string|size:18',
                    'seccion'       => 'required|string',
                    'colonia'       => 'required|string',
                    // Puedes agregar más reglas si algún campo es estrictamente obligatorio
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'clave_elector' => $recordData['clave_elector'] ?? "Registro #{$index}",
                        'id_local' => $recordData['id_local'] ?? null,
                        'tipo_error' => 'Validación',
                        'detalles' => $validator->errors()->all()
                    ];
                    continue; // Saltamos al siguiente registro
                }

                // 2. GEOLOCALIZACIÓN CON GOOGLE MAPS API
                $lat = null;
                $lon = null;

                $direccionParts = array_filter([
                    $recordData['calle_numero'] ?? null,
                    $recordData['colonia'] ?? null,
                    $recordData['codigo_postal'] ?? null,
                    $recordData['municipio'] ?? 'VICTORIA',
                    $recordData['estado'] ?? 'TAMPS'
                ]);

                $direccionConsulta = implode(', ', $direccionParts);

                // Forzamos la lectura de la llave directamente del archivo .env por si la caché falla
                $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? config('services.google_maps.api_key');

                if (!empty($direccionConsulta) && !empty($apiKey)) {
                    try {
                        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                            'address' => $direccionConsulta,
                            'components' => 'country:MX',
                            'key' => $apiKey
                        ]);

                        $data = $response->json();

                        if ($response->successful() && isset($data['status'])) {
                            if ($data['status'] === 'OK') {
                                $location = $data['results'][0]['geometry']['location'];
                                $lat = $location['lat'];
                                $lon = $location['lng'];
                            } else {
                                // Aquí atrapamos el error exacto de Google
                                $errorMessage = $data['error_message'] ?? 'Sin detalle';
                                Log::warning("API Google falló. Status: {$data['status']} | Mensaje: {$errorMessage} | Dirección: {$direccionConsulta}");
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Fallo de red conectando a Google Maps: " . $e->getMessage());
                    }
                } else {
                    Log::error("No se encontró la llave GOOGLE_MAPS_API_KEY en el archivo .env");
                }

                if (empty($lat) || empty($lon)) {
                    $errors[] = [
                        'clave_elector' => $recordData['clave_elector'],
                        'id_local' => $recordData['id_local'] ?? null,
                        'tipo_error' => 'Geolocalización',
                        'detalles' => ['Dirección no encontrada. Google no pudo ubicar este domicilio. Por favor, edita la calle o colonia manualmente.']
                    ];
                    // IMPORTANTE: Hacemos 'continue' para NO guardar en la DB y que el ID no se marque como sincronizado
                    continue;
                }

                // 3. GUARDADO EN BASE DE DATOS
                $ine = IneRecord::updateOrCreate(
                    [
                        'clave_elector' => $recordData['clave_elector']
                    ],
                    [
                        'user_id'          => $request->user()->id,
                        'curp'             => $recordData['curp'] ?? null,
                        'ocr_numero'       => $recordData['ocr_numero'] ?? null,
                        'nombre'           => $recordData['nombre'] ?? 'SIN NOMBRE',
                        'apellido_paterno' => $recordData['apellido_paterno'] ?? '',
                        'apellido_materno' => $recordData['apellido_materno'] ?? null,
                        'fecha_nacimiento' => $recordData['fecha_nacimiento'] ?? null,
                        'sexo'             => $recordData['sexo'] ?? null,
                        'calle_numero'     => $recordData['calle_numero'] ?? null,
                        'colonia'          => $recordData['colonia'],
                        'codigo_postal'    => $recordData['codigo_postal'] ?? null,
                        'municipio'        => $recordData['municipio'] ?? 'VICTORIA',
                        'estado'           => $recordData['estado'] ?? 'TAMPS',
                        'seccion'          => $recordData['seccion'],
                        'vigencia'         => $recordData['vigencia'] ?? null,

                        // Guardamos los datos de la API de Google
                        'latitud'          => $lat,
                        'longitud'         => $lon,

                        'capturado_en'     => $recordData['capturado_en'] ?? now(),
                    ]
                );

                // Confirmamos a la app móvil
                if (isset($recordData['id_local'])) {
                    $syncedLocalIds[] = $recordData['id_local'];
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'clave_elector' => $recordData['clave_elector'] ?? "Registro #{$index}",
                    'id_local' => $recordData['id_local'] ?? null,
                    'tipo_error' => 'Sistema',
                    'detalles' => [$e->getMessage()]
                ];
            }
        }

        return response()->json([
            'message' => count($errors) > 0 ? 'Sincronización con observaciones' : 'Lote procesado',
            'synced_ids' => $syncedLocalIds,
            'errors' => $errors,
            'has_errors' => count($errors) > 0
        ]);
    }

    /**
     * Recibe una foto (frente o reverso) y la asocia a un registro existente.
     */
    public function syncPhoto(Request $request)
    {
        $request->validate([
            'clave_elector' => 'required|string|exists:ine_records,clave_elector',
            'foto'          => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tipo'          => 'required|in:frente,reverso',
        ]);

        $ine = IneRecord::where('clave_elector', $request->clave_elector)->first();
        $folderPath = 'ines/' . $ine->clave_elector;
        $path = $request->file('foto')->store($folderPath, 'local');

        if ($request->tipo === 'frente') {
            if ($ine->foto_frente_path && \Storage::disk('local')->exists($ine->foto_frente_path)) {
                \Storage::disk('local')->delete($ine->foto_frente_path);
            }
            $ine->foto_frente_path = $path;
        } else {
            if ($ine->foto_reverso_path && \Storage::disk('local')->exists($ine->foto_reverso_path)) {
                \Storage::disk('local')->delete($ine->foto_reverso_path);
            }
            $ine->foto_reverso_path = $path;
        }

        $ine->save();

        return response()->json([
            'message' => "Foto de {$request->tipo} sincronizada correctamente.",
            'path'    => $path
        ], 200);
    }
}
