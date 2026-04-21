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
    Route::get('/rastreo-brigadistas', [TrackingController::class, 'obtenerActivos']);

});

require __DIR__.'/auth.php';
