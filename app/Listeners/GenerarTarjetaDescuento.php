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

            // 4. GENERAR LA IMAGEN CON PHP NATIVO (CERO LIBRERÍAS EXTERNAS)
            Log::info("Iniciando generación GD Nativo para tarjeta ID: {$card->id}");

            Storage::disk('public')->makeDirectory('cards');

            // --- VARIABLES DE DISEÑO (Ajusta estos números si queda un poco movido) ---
            $qrSize = 220;         // Tamaño del código QR
            $qrX = 35;             // Posición X del QR (De izquierda a derecha)
            $qrY = 35;             // Posición Y del QR (De arriba hacia abajo)

            $yFolio = 85;          // Posición Y (altura) del Folio dentro de su burbuja
            $centroBurbujaX = 220; // Distancia desde el borde derecho hasta el centro de la burbuja blanca

            $yNombre = 360;        // Altura del nombre (debe quedar sobre 'NEGOCIO AFILIADO')
            // -------------------------------------------------------------------------

            // A) Generar el QR con el nuevo tamaño
            $qrContent = json_encode($qrData);
            $qrImageBinary = QrCode::format('png')->size($qrSize)->margin(0)->generate($qrContent);
            $tempQrPath = storage_path("app/public/temp_qr_{$card->id}.png");
            file_put_contents($tempQrPath, $qrImageBinary);

            // B) Cargar imágenes en la memoria de PHP
            $baseImage = imagecreatefrompng(public_path('templates/tarjeta_base.png'));
            $qrImage = imagecreatefrompng($tempQrPath);

            imagealphablending($baseImage, true);
            imagesavealpha($baseImage, true);

            // C) Pegar el QR más grande
            // imagecopy(destino, origen, dst_x, dst_y, src_x, src_y, ancho, alto)
            imagecopy($baseImage, $qrImage, $qrX, $qrY, 0, 0, $qrSize, $qrSize);

            // D) Preparar la escritura de textos
            $fontPath = public_path('fonts/arial.ttf');
            $folioText = 'FOLIO: ' . $card->folio_formateado;
            // mb_strtoupper asegura que los acentos y eñes se hagan mayúsculas correctamente
            $nombreText = mb_strtoupper(trim($ine->nombre . ' ' . $ine->apellido_paterno . ' ' . $ine->apellido_materno), 'UTF-8');

            $colorNegro = imagecolorallocate($baseImage, 0, 0, 0);
            $colorBlanco = imagecolorallocate($baseImage, 255, 255, 255);

            $imageWidth = imagesx($baseImage);

            // --- ESCRIBIR FOLIO (Centrado en su burbuja derecha) ---
            $fontSizeFolio = 22;
            $bboxFolio = imagettfbbox($fontSizeFolio, 0, $fontPath, $folioText);
            $folioWidth = $bboxFolio[2] - $bboxFolio[0];

            // Calculamos la X restando el centro de la burbuja al ancho total,
            // y luego restamos la mitad de lo que mide el texto para centrarlo perfecto.
            $xFolio = ($imageWidth - $centroBurbujaX) - ($folioWidth / 2);

            imagettftext($baseImage, $fontSizeFolio, 0, $xFolio, $yFolio, $colorNegro, $fontPath, $folioText);

            // --- ESCRIBIR NOMBRE (Centrado perfectamente en la tarjeta completa) ---
            $fontSizeNombre = 26;
            $bboxNombre = imagettfbbox($fontSizeNombre, 0, $fontPath, $nombreText);
            $nombreWidth = $bboxNombre[2] - $bboxNombre[0];
            $xNombre = ($imageWidth / 2) - ($nombreWidth / 2);

            imagettftext($baseImage, $fontSizeNombre, 0, $xNombre, $yNombre, $colorBlanco, $fontPath, $nombreText);

            // E) Guardar la imagen final
            $filename = "cards/tarjeta_{$card->folio_formateado}.png";
            $storagePath = storage_path("app/public/{$filename}");
            imagepng($baseImage, $storagePath);

            // F) Limpiar la memoria del servidor
            imagedestroy($baseImage);
            imagedestroy($qrImage);
            @unlink($tempQrPath);

            // 5. Actualizar ruta en BD
            $card->update(['image_path' => $filename]);

            // 6. Enviar Correo
            Mail::to($card->email_envio)->send(new TarjetaDescuentoEnviada($card));
            $card->update(['enviado_por_correo' => true]);

            Log::info("Tarjeta generada exitosamente con diseño ajustado: {$filename}");

        } catch (\Exception $e) {
            Log::error("Error generando tarjeta de descuento para INE ID {$ine->id}: " . $e->getMessage());
            if(isset($card)) $card->delete();
        }
    }
}
