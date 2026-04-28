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

            // A) Generar el QR
            $qrContent = json_encode($qrData);
            $qrImageBinary = QrCode::format('png')->size(130)->margin(0)->generate($qrContent);
            $tempQrPath = storage_path("app/public/temp_qr_{$card->id}.png");
            file_put_contents($tempQrPath, $qrImageBinary);

            // B) Cargar imágenes en la memoria de PHP
            $baseImage = imagecreatefrompng(public_path('templates/tarjeta_base.png'));
            $qrImage = imagecreatefrompng($tempQrPath);

            // Respetar transparencias
            imagealphablending($baseImage, true);
            imagesavealpha($baseImage, true);

            // C) Pegar el QR (Destino, Origen, dst_x, dst_y, src_x, src_y, ancho, alto)
            // Lo ponemos a 20px de la izquierda y 20px de arriba
            imagecopy($baseImage, $qrImage, 20, 20, 0, 0, 130, 130);

            // D) Preparar la escritura de textos
            $fontPath = public_path('fonts/fuente.ttf');
            $folioText = 'FOLIO: ' . $card->folio_formateado;
            $nombreText = mb_strtoupper(trim($ine->nombre . ' ' . $ine->apellido_paterno), 'UTF-8');

            // Crear colores (RGB)
            $colorNegro = imagecolorallocate($baseImage, 0, 0, 0);
            $colorBlanco = imagecolorallocate($baseImage, 255, 255, 255);

            // Obtenemos el ancho total de tu plantilla azul para calcular posiciones
            $imageWidth = imagesx($baseImage);

            // --- ESCRIBIR FOLIO (Alineado a la derecha) ---
            // Calculamos cuánto mide el texto para empujarlo a la orilla
            $bboxFolio = imagettfbbox(20, 0, $fontPath, $folioText);
            $folioWidth = $bboxFolio[2] - $bboxFolio[0];
            $xFolio = $imageWidth - $folioWidth - 30; // 30px de margen derecho

            // imagettftext(imagen, tamaño_fuente, ángulo, x, y, color, fuente, texto)
            imagettftext($baseImage, 20, 0, $xFolio, 50, $colorNegro, $fontPath, $folioText);

            // --- ESCRIBIR NOMBRE (Centrado perfectamente) ---
            $bboxNombre = imagettfbbox(24, 0, $fontPath, $nombreText);
            $nombreWidth = $bboxNombre[2] - $bboxNombre[0];
            $xNombre = ($imageWidth / 2) - ($nombreWidth / 2);

            // Y=380 es un estimado hacia abajo de la tarjeta, ajústalo si queda muy arriba o abajo
            imagettftext($baseImage, 24, 0, $xNombre, 380, $colorBlanco, $fontPath, $nombreText);

            // E) Guardar la imagen final
            $filename = "cards/tarjeta_{$card->folio_formateado}.png";
            $storagePath = storage_path("app/public/{$filename}");
            imagepng($baseImage, $storagePath);

            // F) Limpiar la memoria del servidor y borrar temporales
            imagedestroy($baseImage);
            imagedestroy($qrImage);
            @unlink($tempQrPath);

            // 5. Actualizar ruta en BD
            $card->update(['image_path' => $filename]);

            // 6. Enviar Correo
            Mail::to($card->email_envio)->send(new TarjetaDescuentoEnviada($card));
            $card->update(['enviado_por_correo' => true]);

            Log::info("Tarjeta generada exitosamente con GD Nativo: {$filename}");

        } catch (\Exception $e) {
            Log::error("Error generando tarjeta de descuento para INE ID {$ine->id}: " . $e->getMessage());
            if(isset($card)) $card->delete();
        }
    }
}
