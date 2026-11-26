<?php

use App\Http\Controllers\Admini\TrackingController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\SpatialController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\JornadaController;
use App\Http\Controllers\Api\V1\PadronSyncController;
use App\Http\Controllers\Api\V1\ParticipacionController;
use App\Http\Controllers\Api\V1\SolicitudLogisticaController;
use App\Http\Middleware\EnsureMobileFieldRole;
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

    // La app envía (POST)
    Route::post('/ubicacion-brigadista', [TrackingController::class, 'reportarUbicacion']);

    // Rutas protegidas de servicios geoespaciales
    Route::prefix('spatial')->group(function () {
        Route::get('/secciones-geojson', [SpatialController::class, 'seccionesGeoJson']);
        Route::post('/locate-point', [SpatialController::class, 'locatePoint']);
        Route::get('/audit-summary', [SpatialController::class, 'auditSummary']);
    });

    // API v1 — Módulo de Participación Ciudadana y Gestión Logística (roles móviles de campo)
    Route::prefix('v1')
        ->middleware(EnsureMobileFieldRole::class)
        ->group(function () {
            Route::post('/devices/token', [DeviceTokenController::class, 'store']);
            Route::delete('/devices/token', [DeviceTokenController::class, 'destroy']);

            Route::get('/jornadas/activa', [JornadaController::class, 'activa']);
            Route::get('/padron/sync', [PadronSyncController::class, 'index']);
            Route::post('/participacion', [ParticipacionController::class, 'store']);

            Route::get('/solicitudes', [SolicitudLogisticaController::class, 'index']);
            Route::patch('/solicitudes/{solicitud}/estatus', [SolicitudLogisticaController::class, 'updateEstatus']);
        });
});



