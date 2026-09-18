<?php

namespace App\Services;

use App\Contracts\SpatialServiceInterface;
use App\Models\Apoyo;
use App\Models\IneRecord;
use App\Models\SeccionElectoral;
use Illuminate\Support\Facades\DB;

class SpatialService implements SpatialServiceInterface
{
    public function __construct(
        protected int $srid = 4326,
        protected string $axisOrder = 'axis-order=long-lat',
        protected int $defaultEntidad = 28,
        protected int $defaultMunicipio = 41
    ){}

    /**
     * Determina la sección electoral que contiene un punto GPS (Point in Polygon) 
     */
    public function findSeccionByPoint(float $latitud, float $longitud): ?SeccionElectoral
    {
        return SeccionElectoral::whereRaw(
            "ST_Contains(poligono, ST_GeomFromText(?, ?, ?))",
            ["POINT({$longitud} {$latitud})", $this->srid, $this->axisOrder]
        )->first();
    }

    /**
     * Auto-asigna la sección territorial a los registros de apoyos pendientes
     * soportan ejecucion por lotes Eloquent o via UPDATE JOIN masivo en MySQL
     */
    public function assignSeccionToApoyos(bool $useBulkSql = false): array
    {
        if ($useBulkSql) {
            // Actualización masiva en una sola sentencia SQL de alto rendimiento
            $afectados = DB::affectingStatement("
                UPDATE apoyos a
                    JOIN secciones_electorales s
                      ON ST_Contains(s.poligono, ST_GeomFromText(CONCAT('POINT(', a.longitud, ' ', a.latitud, ')'), {$this->srid}, '{$this->axisOrder}'))
                    SET a.seccion = s.seccion,
                        a.updated_at = NOW()
                    WHERE a.seccion IS NULL
                      AND a.latitud IS NOT NULL
                      AND a.longitud IS NOT NULL
            ");

            return [
                'modo' => 'sql_bulk',
                'asignados' => $afectados,
            ];
        }

        // Modo granular con Eloquent
        $apoyosSinSeccion = Apoyo::whereNotNull('latitud')
                                ->whereNotNull('longitud')
                                ->whereNull('seccion')
                                ->get();

        $asignados = 0;
        $sinCobertura = 0;

        foreach ($apoyosSinSeccion as $apoyo) {
            $seccion = $this->findSeccionByPoint((float)$apoyo->latitud, (float)$apoyo->longitud);

            if ($seccion){
                $apoyo->seccion = $seccion->seccion;
                $apoyo->saveQuietly();
                $asignados++;
            } else {
                $sinCobertura++;
            }
        }

        return [
            'modo' => 'eloquent',
            'total_procesados' => $apoyosSinSeccion->count(),
            'asignados' => $asignados,
            'sin_cobertura' => $sinCobertura
        ];
    }

    /**
     * Audita la consistencia entre la sección impresa en el INE y las coordenadas GPS
     */
    public function auditIneRecords(int $limit = 500): array
    {
        $records = IneRecord::whereNotNull('latitud')
                            ->whereNotNull('longitud')
                            ->whereNotNull('seccion')
                            ->limit($limit)
                            ->get();
        
        $coincidentes = 0;
        $discrepancias = [];
        $fueraDePoligonos = [];

        foreach ($records as $record) {
            $seccionGps = $this->findSeccionByPoint((float)$record->latitud, (float)$record->longitud);

            if (!$seccionGps) {
                $fueraDePoligonos[] = [
                    'id' => $record->id,
                    'clave_elector' => $record->clave_elector,
                    'latitud' => $record->latitud,
                    'longitud' => $record->longitud,
                    'motivo' => 'Coordenadas fuera del poligono municipal'
                ];
            } else if ($seccionGps->seccion === $record->seccion) {
                $coincidentes++;
            } else {
                $discrepancias[] = [
                    'id' => $record->id,
                    'clave_elector' => $record->clave_elector,
                    'seccion_credencial' => $record->seccion,
                    'clave_credencial' => $record->seccion,
                    'seccion_gps_real' => $seccionGps->seccion,
                    'distrito_local' => $seccionGps->distrito_local,
                    'latitud' => $record->latitud,
                    'longitud' => $record->longitud
                ];
            }
        }
        
        $total = $records->count();

        return [
            'total_analizados' => $total,
            'coincidentes' => $coincidentes,
            'porcentaje_consistencia' => $total > 0 ? round(($coincidentes / $total) * 100, 2) : 0,
            'total_discrepancias' => count($discrepancias),
            'total_fuera_cobertura' => count($fueraDePoligonos),
            'discrepancias' => $discrepancias,
            'fuera_de_poligonos' => $fueraDePoligonos
        ];
    }

    public function getSeccionesGeoJsonWithMetrics(?int $municipio = null): array
    {
        $municipio = $municipio ?? $this->defaultMunicipio;

        // Agrupación atributiva rápida O(1) con índice B-Tree
        $metricasInes = IneRecord::select('seccion', DB::raw('COUNT(*) as total'))
                                    ->groupBy('seccion')
                                    ->pluck('total','seccion')
                                    ->toArray();

        $metricasApoyos = Apoyo::whereNotNull('seccion')
                                    ->select('seccion', DB::raw('COUNT(*) as total'))
                                    ->groupBy('seccion')
                                    ->pluck('total', 'seccion')
                                    ->toArray();

        // Traer los poligonos convertidos a GeoJson por MySQL nativo
        $secciones = SeccionElectoral::forMunicipio($municipio)
                                        ->withGeoJson()
                                        ->get();
        $features = [];

        foreach ($secciones as $sec) {
            $features[]  = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $sec->id,
                    'seccion' => $sec->seccion,
                    'municipio' => $sec->municipio,
                    'distrito_federal' => $sec->distrito_federal,
                    'distrito_local' => $sec->distrito_local,
                    'tipo' => $sec->tipo,
                    'total_simpatizantes' => $metricasInes[$sec->seccion] ?? 0,
                    'total_apoyos' => $metricasApoyos[$sec->seccion] ?? 0,
                ],
                'geometry' => json_decode($sec->geojson)
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features
        ];
    }
}
