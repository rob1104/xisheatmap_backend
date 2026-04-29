<?php

use App\Http\Controllers\Admin\HeatMapController;
use App\Http\Controllers\Admin\IneRecordController;
use App\Http\Controllers\Admin\TarjetaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admini\TrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\CheckAdminAccess;
use App\Mail\TarjetaDescuentoEnviada;
use App\Models\IneRecord;
use App\Models\TarjetaDescuento;
use App\Models\User;
use Carbon\Carbon;
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
        $kpis = [
            // 1. Total histórico de registros
            'total_ines' => IneRecord::count(),

            // 2. Registros de hoy (usando Carbon para la fecha actual)
            'capturas_hoy' => IneRecord::whereDate('created_at', Carbon::today())->count(),

            // 3. Usuarios con el rol de brigadista
            'en_campo' => User::where('role', 'brigadista')->count(),

            // 4. Total de tarjetas generadas
            'tarjetas' => TarjetaDescuento::count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'kpis' => $kpis
        ]);
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

    Route::get('/tarjetas', [TarjetaController::class, 'index'])->name('tarjetas.index');
    Route::put('/tarjetas/{tarjeta}/email', [TarjetaController::class, 'updateEmail'])->name('tarjetas.updateEmail');
    Route::post('/tarjetas/{tarjeta}/reenviar', [TarjetaController::class, 'reenviarCorreo'])->name('tarjetas.reenviar');
    Route::delete('/expedientes/{record}', [IneRecordController::class, 'destroy'])->name('expedientes.destroy');

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

Route::get('/test-correo', function () {
    $emailDestino = 'rescamilla@xis.mx'; // Tu correo real

    // 1. Obtenemos la última tarjeta real de tu BD para que el correo tenga datos válidos
    $card = TarjetaDescuento::with('ineRecord')->latest()->first();

    if (!$card) {
        return "Error: Necesitas tener al menos un registro en la tabla tarjetas_descuento para hacer la prueba.";
    }

    // --- 2. VARIABLES DE DISEÑO ---
    $qrSize = 220;
    $qrX = 35;
    $qrY = 35;
    $yFolio = 85;
    $centroBurbujaX = 220;
    $yNombre = 360;
    $fontSizeFolio = 22;
    $fontSizeNombre = 26;

    Storage::disk('public')->makeDirectory('cards');

    // 3. GENERAR QR
    $qrContent = json_encode($card->qr_data_json ?? ['test' => '123']);
    $qrImageBinary = QrCode::format('png')->size($qrSize)->margin(0)->generate($qrContent);
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

    // Textos reales de la BD
    $folioText = 'FOLIO: ' . $card->folio_formateado;
    $ine = $card->ineRecord;
    $nombreText = mb_strtoupper(trim($ine->nombre . ' ' . $ine->apellido_paterno . ' ' . $ine->apellido_materno), 'UTF-8');

    // Matemáticas del Folio
    $bboxFolio = imagettfbbox($fontSizeFolio, 0, $fontPath, $folioText);
    $folioWidth = $bboxFolio[2] - $bboxFolio[0];
    $xFolio = ($imageWidth - $centroBurbujaX) - ($folioWidth / 2);
    imagettftext($baseImage, $fontSizeFolio, 0, $xFolio, $yFolio, $colorNegro, $fontPath, $folioText);

    // Matemáticas del Nombre
    $bboxNombre = imagettfbbox($fontSizeNombre, 0, $fontPath, $nombreText);
    $nombreWidth = $bboxNombre[2] - $bboxNombre[0];
    $xNombre = ($imageWidth / 2) - ($nombreWidth / 2);
    imagettftext($baseImage, $fontSizeNombre, 0, $xNombre, $yNombre, $colorBlanco, $fontPath, $nombreText);

    // 7. GUARDAR LA IMAGEN DE PRUEBA
    $filename = "cards/tarjeta_TEST_CORREO.png";
    $storagePath = storage_path("app/public/{$filename}");
    imagepng($baseImage, $storagePath);

    // Limpiar memoria
    imagedestroy($baseImage);
    imagedestroy($qrImage);
    @unlink($tempQrPath);

    // 8. ENVIAR EL CORREO
    // Le inyectamos temporalmente la ruta de nuestra nueva imagen a la tarjeta para que el adjunto salga bien
    $card->image_path = $filename;

    // Disparamos el correo
    Mail::to($emailDestino)->send(new TarjetaDescuentoEnviada($card));

    return "<h2>¡Prueba enviada!</h2> Revisa la bandeja de entrada de <b>{$emailDestino}</b>.<br><br> Si el diseño se ve bien, copia las coordenadas a tu Listener.";
});

require __DIR__.'/auth.php';
