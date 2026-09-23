<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IneRecord;
use App\Models\Apoyo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HeatMapController extends Controller
{
    public function index()
    {
        // Filtramos solo los registros que lograron geolocalizarse
        $coordenadas = IneRecord::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->select('latitud', 'longitud', 'colonia', 'seccion')
            ->get();

        $apoyos = Apoyo::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->select('id', 'latitud', 'longitud', 'nombre', 'estatus_de_apoyo', 'apoyo')
            ->get();

        $casas = \App\Models\CasaMesil::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->select('id', 'latitud', 'longitud', 'nombre')
            ->get();

        return Inertia::render('Admin/HeatMap/Index', [
            'coordenadas' => $coordenadas,
            'apoyos' => $apoyos,
            'casas' => $casas,
            // Pasamos la llave de forma segura desde el .env a la vista
            'googleApiKey' => config('services.google_maps.api_key')
        ]);
    }
}
