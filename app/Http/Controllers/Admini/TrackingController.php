<?php

namespace App\Http\Controllers\Admini;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function reportarUbicacion(Request $request)
    {
        $request->validate([
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
        ]);

        $user = $request->user(); // Identifica al brigadista por su Token

        $user->update([
            'latitud_actual' => $request->latitud,
            'longitud_actual' => $request->longitud,
            'ultima_conexion_app' => now(), // Sella la hora exacta
        ]);

        return response()->json(['status' => 'Ubicación actualizada']);
    }

    /**
     * Endpoint 2: El Mapa Web llama aquí cada 10 segundos
     */
    public function obtenerActivos()
    {
        // Filtramos para traer SOLO a los que reportaron en los últimos 5 minutos.
        // Si alguien apaga su cel, a los 5 minutos desaparece del mapa automáticamente.
        $haceCincoMinutos = Carbon::now()->subMinutes(5);

        $brigadistas = User::whereNotNull('latitud_actual')
            ->whereNotNull('longitud_actual')
            ->where('ultima_conexion_app', '>=', $haceCincoMinutos)
            ->get(['id', 'name', 'latitud_actual as lat', 'longitud_actual as lng', 'ultima_conexion_app']);

        // Formateamos la hora para que se vea bonita en el mapa
        $brigadistas->transform(function ($user) {
            $user->ultima_conexion = Carbon::parse($user->ultima_conexion_app)->diffForHumans();
            return $user;
        });

        return response()->json($brigadistas);
    }
}
