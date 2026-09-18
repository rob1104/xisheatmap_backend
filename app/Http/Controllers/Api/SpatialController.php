<?php

namespace App\Http\Controllers\Api;

use App\Contracts\SpatialServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SpatialController extends Controller
{
    public function __construct(
        protected SpatialServiceInterface $spatial
    ){}

    public function seccionesGeoJson(Request $request)
    {
        $municipio = $request->query('municipio') ? (int)$request->query('municipio') : null;
        return response()->json($this->spatial->getSeccionesGeoJsonWithMetrics($municipio));
    }

    public function locatePoint(Request $request)
    {
        $request->validate([
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
        ]);

        $seccion = $this->spatial->findSeccionByPoint(
            (float)$request->latitud,
            (float)$request->longitud
        );
        
        if (!$seccion) {
            return response()->json(['message' => 'Punto fuera de cobertura electoral'], 404);
        }

        return response()->json($seccion);
    }

    public function auditSummary()
    {
        return response()->json($this->spatial->auditIneRecords(1000));
    }
}
