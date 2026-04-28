<?php

use App\Http\Controllers\Admin\HeatMapController;
use App\Http\Controllers\Admin\IneRecordController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admini\TrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckAdminAccess;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Ruta principal, redirige al login si no hay sesión
Route::get('/', function () {
    return redirect()->route('login');
});

// Grupo principal protegido con Auth, Verified y nuestro nuevo CheckAdminAccess
Route::middleware(['auth', CheckAdminAccess::class])->group(function () {

    // ---------------------------------------------------
    // VISTAS DEL PANEL DE CONTROL (ADMIN / SUPERVISOR)
    // ---------------------------------------------------
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    Route::get('/expedientes', [IneRecordController::class, 'index'])->name('expedientes.index');
    Route::get('/expedientes/foto/{id}/{tipo}', [IneRecordController::class, 'showPhoto'])
        ->name('expedientes.foto');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::get('/heatmap', [HeatMapController::class, 'index'])->name('heatmap.index');


    // ---------------------------------------------------
    // RUTAS DE PERFIL (Generadas por Breeze)
    // ---------------------------------------------------
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // El mapa web consulta (GET) - Esta es la que pegamos en tu Vue hace rato
    Route::get('/rastreo-brigadistas', [TrackingController::class, 'obtenerActivos'])->name('rastreo-brigadistas');

});

// Pega esto al final de routes/web.php

Route::get('/test-tarjeta', function () {
    // 1. DATOS FICTICIOS
    $folioFicticio = 'TD26-99999';
    $nombreFicticio = 'ROBERTO EZEQUIEL ESCAMILLA';
    $qrData = ['f' => $folioFicticio, 'c' => 'XAXX010101000', 'n' => $nombreFicticio];

    // 2. VARIABLES DE DISEÑO (¡Estas son las que vas a modificar!)
    $qrSize = 220;         // Tamaño del código QR
    $qrX = 35;             // Posición X del QR (Izquierda/Derecha)
    $qrY = 35;             // Posición Y del QR (Arriba/Abajo)

    $yFolio = 85;          // Posición Y (altura) del Folio
    $centroBurbujaX = 220; // Ajusta esto para mover el folio izquierda/derecha

    $yNombre = 360;        // Altura del nombre
    $fontSizeFolio = 22;   // Tamaño letra del folio
    $fontSizeNombre = 26;  // Tamaño letra del nombre

    // 3. GENERAR QR TEMPORAL
    $qrContent = json_encode($qrData);
    $qrImageBinary = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size($qrSize)->margin(0)->generate($qrContent);
    $tempQrPath = storage_path("app/public/temp_qr_test.png");
    file_put_contents($tempQrPath, $qrImageBinary);

    // 4. CARGAR IMÁGENES
    $baseImage = imagecreatefrompng(public_path('templates/tarjeta_base.png'));
    $qrImage = imagecreatefrompng($tempQrPath);
    imagealphablending($baseImage, true);
    imagesavealpha($baseImage, true);

    // 5. PEGAR QR
    imagecopy($baseImage, $qrImage, $qrX, $qrY, 0, 0, $qrSize, $qrSize);

    // 6. ESCRIBIR TEXTOS
    $fontPath = public_path('fonts/arial.ttf');
    $colorNegro = imagecolorallocate($baseImage, 0, 0, 0);
    $colorBlanco = imagecolorallocate($baseImage, 255, 255, 255);
    $imageWidth = imagesx($baseImage);

    // Matemáticas del Folio
    $folioText = 'FOLIO: ' . $folioFicticio;
    $bboxFolio = imagettfbbox($fontSizeFolio, 0, $fontPath, $folioText);
    $folioWidth = $bboxFolio[2] - $bboxFolio[0];
    $xFolio = ($imageWidth - $centroBurbujaX) - ($folioWidth / 2);
    imagettftext($baseImage, $fontSizeFolio, 0, $xFolio, $yFolio, $colorNegro, $fontPath, $folioText);

    // Matemáticas del Nombre
    $bboxNombre = imagettfbbox($fontSizeNombre, 0, $fontPath, $nombreFicticio);
    $nombreWidth = $bboxNombre[2] - $bboxNombre[0];
    $xNombre = ($imageWidth / 2) - ($nombreWidth / 2);
    imagettftext($baseImage, $fontSizeNombre, 0, $xNombre, $yNombre, $colorBlanco, $fontPath, $nombreFicticio);

    // 7. MOSTRAR DIRECTAMENTE EN EL NAVEGADOR
    ob_start(); // Iniciar captura de salida
    imagepng($baseImage); // Dibujar imagen en la memoria
    $imageContent = ob_get_clean(); // Guardar la imagen en una variable

    // Limpiar memoria RAM
    imagedestroy($baseImage);
    imagedestroy($qrImage);
    @unlink($tempQrPath);

    // Devolver como respuesta HTTP de imagen
    return response($imageContent, 200)->header('Content-Type', 'image/png');
});

require __DIR__.'/auth.php';
