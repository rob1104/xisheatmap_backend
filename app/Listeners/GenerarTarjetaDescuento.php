<?php
namespace App\Listeners;

use App\Events\IneSincronizada;
use App\Models\TarjetaDescuento;
use App\Mail\TarjetaDescuentoEnviada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

// Nuevos Imports para generar la imagen con PHP
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerarTarjetaDescuento implements ShouldQueue
{
    public function handle(IneSincronizada $event)
    {
        $ine = $event->ineRecord;

        // 1. Verificaciones de seguridad
        if (!$ine->correo || TarjetaDescuento::where('curp', $ine->curp)->exists()) {
            // No tiene correo registrado o ya tiene una tarjeta generada (evita duplicados si resincronizan)
            return;
        }

        try {
            // 2. Crear el registro preliminar en la base de datos para obtener el ID secuencial
            $card = TarjetaDescuento::create([
                'ine_record_id' => $ine->id,
                'user_id' => $ine->user_id,
                'curp' => $ine->curp,
                'email_envio' => $ine->correo,
                'fecha_validez_fin' => now()->addYear(), // Válida por un año
                'enviado_por_correo' => false,
                'qr_data_json' => [] // Temporal
            ]);

            // 3. Preparar los datos del QR (JSON)
            $verificationUrl = config('app.url') . "/verificar/tarjeta/{$card->id}";

            $qrData = [
                'f' => $card->folio_formateado,
                'c' => $ine->curp,
                'n' => trim($ine->nombre . ' ' . $ine->apellido_paterno . ' ' . $ine->apellido_materno), // Evitamos errores si no tienes el accessor nombre_completo
                's' => $ine->seccion,
                'v' => $card->fecha_validez_fin->format('Y-m-d'),
                'u' => $verificationUrl
            ];

            // Actualizamos el registro con el JSON real
            $card->update(['qr_data_json' => $qrData]);

            // 4. GENERAR LA IMAGEN CON PHP NATIVO (INTERVENTION IMAGE)
            Log::info("Iniciando generación PHP de imagen para tarjeta ID: {$card->id}");

            // Aseguramos que el directorio de destino exista
            Storage::disk('public')->makeDirectory('cards');

            // A) Generar el QR en formato PNG binario y guardarlo temporalmente
            $qrContent = json_encode($qrData);
            $qrImageBinary = QrCode::format('png')->size(130)->margin(0)->generate($qrContent);
            $tempQrPath = storage_path("app/public/temp_qr_{$card->id}.png");
            file_put_contents($tempQrPath, $qrImageBinary);

            // B) Inicializar el manejador de imágenes
            $manager = new ImageManager(new Driver());

            // C) Cargar tu plantilla base (Asegúrate de subir la imagen azul de MESIL a esta ruta)
            $image = $manager->read(public_path('templates/tarjeta_base.png'));

            // D) Pegar el QR en la esquina superior izquierda
            // Los números (20, 20) son la distancia en pixeles desde el borde izquierdo y superior
            $image->place($tempQrPath, 'top-left', 20, 20);

            // E) Escribir los textos sobre la imagen
            // Asegúrate de subir tu fuente a esta ruta
            $fontPath = public_path('fonts/fuente.ttf');

            $folio = $card->folio_formateado;
            $nombre = trim($ine->nombre . ' ' . $ine->apellido_paterno);
            $vigencia = 'Válida hasta: ' . $card->fecha_validez_fin->format('d/M/Y');

            // Escribir el Folio (Arriba a la derecha)
            $image->text('FOLIO: ' . $folio, 650, 60, function($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(28);
                $font->color('#000000'); // Letra negra
                $font->align('right'); // Alineado a la derecha
            });

            // Escribir el Nombre del Simpatizante (Abajo en el centro)
            $image->text($nombre, 350, 380, function($font) use ($fontPath) {
                $font->file($fontPath);
                $font->size(24);
                $font->color('#FFFFFF'); // Letra blanca
                $font->align('center');
            });

            // F) Guardar la imagen final ensamblada
            $filename = "cards/tarjeta_{$card->folio_formateado}.png";
            $storagePath = storage_path("app/public/{$filename}");
            $image->save($storagePath);

            // Limpiar: Borrar el QR temporal para no acumular basura
            @unlink($tempQrPath);

            // 5. Actualizar ruta de imagen en la base de datos
            $card->update(['image_path' => $filename]);

            // 6. Enviar Correo Electrónico con Enlace
            Mail::to($card->email_envio)->send(new TarjetaDescuentoEnviada($card));
            $card->update(['enviado_por_correo' => true]);

            Log::info("Tarjeta generada exitosamente en PHP: {$filename}");

        } catch (\Exception $e) {
            Log::error("Error generando tarjeta de descuento para INE ID {$ine->id}: " . $e->getMessage());
            // Si algo falla catastróficamente, borramos el registro huérfano
            if(isset($card)) $card->delete();
        }
    }
}
