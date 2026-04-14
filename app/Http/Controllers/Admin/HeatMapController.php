<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IneRecord;
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

        return Inertia::render('Admin/HeatMap/Index', [
            'coordenadas' => $coordenadas,
            // Pasamos la llave de forma segura desde el .env a la vista
            'googleApiKey' => config('services.google_maps.api_key')
        ]);
    }
}
