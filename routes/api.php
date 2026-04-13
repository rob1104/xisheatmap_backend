<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Ruta pública para iniciar sesión desde la app móvil
Route::post('/login', [SyncController::class, 'login']);

// 2. Grupo de rutas protegidas por Sanctum (requieren el token)
Route::middleware('auth:sanctum')->group(function () {
    // Devuelve los datos del usuario logueado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
        //Endpoint principal para recibir el lote de credenciales
    Route::post('/sync', [SyncController::class, 'sync']);
    Route::post('/sync-photo', [SyncController::class, 'syncPhoto']);
});


