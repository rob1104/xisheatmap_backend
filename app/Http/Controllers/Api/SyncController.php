<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IneRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
     * Recibe un array de registros locales y los inserta/actualiza en MariaDB.
     */
    public function sync(Request $request)
    {
        // Validamos que la app nos mande un array llamado 'records'
        $request->validate([
            'records' => 'required|array',
            'records.*.clave_elector' => 'required|string|size:18',
            'records.*.seccion' => 'required|string',
            'records.*.colonia' => 'required|string',
        ]);

        $syncedLocalIds = []; // Guardaremos los IDs locales de SQLite para decirle a la app cuáles borrar
        $errors = [];

        foreach ($request->records as $recordData) {
            try {
                // El updateOrCreate busca por la clave de elector.
                // Si existe, lo actualiza (por si el capturista corrigió un error ortográfico en su app).
                // Si no existe, lo crea nuevo.
                $ine = IneRecord::updateOrCreate(
                    [
                        'clave_elector' => $recordData['clave_elector']
                    ],
                    [
                        'user_id'          => $request->user()->id, // Quien está logueado mandando el dato
                        'curp'             => $recordData['curp'] ?? null,
                        'ocr_numero'       => $recordData['ocr_numero'] ?? null,
                        'nombre'           => $recordData['nombre'],
                        'apellido_paterno' => $recordData['apellido_paterno'],
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
                        'latitud'          => $recordData['latitud'] ?? null,
                        'longitud'         => $recordData['longitud'] ?? null,
                        'capturado_en'     => $recordData['capturado_en'] ?? now(),
                    ]
                );

                // Si la app móvil mandó su ID local (ej. el id de SQLite), lo guardamos en la lista de éxito
                if (isset($recordData['id_local'])) {
                    $syncedLocalIds[] = $recordData['id_local'];
                }

            } catch (\Exception $e) {
                // Si falla un registro, no detenemos todo el lote, solo lo anotamos en los errores.
                $errors[] = [
                    'clave_elector' => $recordData['clave_elector'],
                    'error' => 'Error al guardar: ' . $e->getMessage()
                ];
            }
        }

        // Le respondemos a la app móvil con lo que salió bien y lo que salió mal
        return response()->json([
            'message' => 'Lote procesado',
            'synced_ids' => $syncedLocalIds,
            'errors' => $errors
        ]);
    }

    /**
     * Recibe una foto (frente o reverso) y la asocia a un registro existente.
     */
    public function syncPhoto(Request $request)
    {
        // 1. Validamos que venga la foto, el tipo y que el registro ya exista
        $request->validate([
            'clave_elector' => 'required|string|exists:ine_records,clave_elector',
            'foto'          => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB por foto
            'tipo'          => 'required|in:frente,reverso',
        ]);

        // 2. Buscamos el registro en la base de datos
        $ine = IneRecord::where('clave_elector', $request->clave_elector)->first();

        // 3. Guardamos el archivo en una CARPETA PRIVADA.
        // Se guardará en: storage/app/ines/{clave_elector}/nombre_aleatorio.jpg
        // No usamos el disco 'public' por seguridad.
        $folderPath = 'ines/' . $ine->clave_elector;
        $path = $request->file('foto')->store($folderPath, 'local');

        // 4. Actualizamos el campo correspondiente en la base de datos
        if ($request->tipo === 'frente') {
            // Si ya había una foto antes (ej. el usuario la volvió a tomar), podemos borrar la vieja
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
