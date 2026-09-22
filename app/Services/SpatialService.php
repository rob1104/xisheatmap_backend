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
        protected int $defaultMunicipio = 41,
        protected string $engine = 'auto'
    ) {}

    /**
     * Configura el motor GIS ('auto', 'mysql', 'mariadb').
     */
    public function setEngine(string $engine): self
    {
        $this->engine = strtolower($engine);
        return $this;
    }

    /**
     * Obtiene el motor GIS actual.
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Determina si el motor activo se comporta como MariaDB (sin el 3er argumento en ST_GeomFromText).
     */
    public function isMariaDb(): bool
    {
        if ($this->engine === 'mariadb') {
            return true;
        }

        if ($this->engine === 'mysql') {
            return false;
        }

        try {
            $version = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
            return stripos($version, 'MariaDB') !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determina la sección electoral que contiene un punto GPS (Point in Polygon)
     */
    public function findSeccionByPoint(float $latitud, float $longitud): ?SeccionElectoral
    {
        $wkt = "POINT({$longitud} {$latitud})";

        if ($this->isMariaDb()) {
            // MariaDB 10.4.13 no admite un tercer argumento en ST_GeomFromText.
            // Siempre asume el orden de coordenadas (X=longitud, Y=latitud).
            return SeccionElectoral::whereRaw(
                "ST_Contains(poligono, ST_GeomFromText(?, ?))",
                [$wkt, $this->srid]
            )->first();
        }

        // MySQL 8.0 requiere 'axis-order=long-lat' con SRID 4326 para interpretar WKT como (long lat).
        return SeccionElectoral::whereRaw(
            "ST_Contains(poligono, ST_GeomFromText(?, ?, ?))",
            [$wkt, $this->srid, $this->axisOrder]
        )->first();
    }

    /**
     * Auto-asigna la sección territorial a los registros de apoyos pendientes.
     * Soporta ejecución por lotes con Eloquent o vía UPDATE JOIN masivo adaptado al motor.
     */
    public function assignSeccionToApoyos(bool $useBulkSql = false): array
    {
        if ($useBulkSql) {
            // Generar la sentencia adaptada a MariaDB vs MySQL 8
            $pointSql = $this->isMariaDb()
                ? "ST_GeomFromText(CONCAT('POINT(', a.longitud, ' ', a.latitud, ')'), {$this->srid})"
                : "ST_GeomFromText(CONCAT('POINT(', a.longitud, ' ', a.latitud, ')'), {$this->srid}, '{$this->axisOrder}')";

            $afectados = DB::affectingStatement("
                UPDATE apoyos a
                    JOIN secciones_electorales s
                      ON ST_Contains(s.poligono, {$pointSql})
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

        // Modo granular con Eloquent utilizando chunkById para no saturar memoria
        $asignados = 0;
        $sinCobertura = 0;
        $totalProcesados = 0;

        Apoyo::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->whereNull('seccion')
            ->chunkById(200, function ($apoyos) use (&$asignados, &$sinCobertura, &$totalProcesados) {
                foreach ($apoyos as $apoyo) {
                    $totalProcesados++;
                    $seccion = $this->findSeccionByPoint((float)$apoyo->latitud, (float)$apoyo->longitud);

                    if ($seccion) {
                        $apoyo->seccion = $seccion->seccion;
                        $apoyo->saveQuietly();
                        $asignados++;
                    } else {
                        $sinCobertura++;
                    }
                }
            });

        return [
            'modo' => 'eloquent',
            'total_procesados' => $totalProcesados,
            'asignados' => $asignados,
            'sin_cobertura' => $sinCobertura
        ];
    }

    /**
     * Auto-asigna o sincroniza la sección territorial a los registros de simpatizantes INE según su GPS real.
     * Soporta ejecución por lotes con Eloquent o vía UPDATE JOIN masivo adaptado al motor.
     */
    public function assignSeccionToIneRecords(bool $useBulkSql = false, bool $force = false): array
    {
        if ($useBulkSql) {
            $pointSql = $this->isMariaDb()
                ? "ST_GeomFromText(CONCAT('POINT(', i.longitud, ' ', i.latitud, ')'), {$this->srid})"
                : "ST_GeomFromText(CONCAT('POINT(', i.longitud, ' ', i.latitud, ')'), {$this->srid}, '{$this->axisOrder}')";

            $whereClause = $force
                ? "i.latitud IS NOT NULL AND i.longitud IS NOT NULL AND (i.seccion_gps IS NULL OR i.seccion_gps != s.seccion)"
                : "i.seccion_gps IS NULL AND i.latitud IS NOT NULL AND i.longitud IS NOT NULL";

            $afectados = DB::affectingStatement("
                UPDATE ine_records i
                    JOIN secciones_electorales s
                      ON ST_Contains(s.poligono, {$pointSql})
                    SET i.seccion_gps = s.seccion,
                        i.updated_at = NOW()
                    WHERE {$whereClause}
            ");

            return [
                'modo' => 'sql_bulk',
                'asignados' => $afectados,
            ];
        }

        $asignados = 0;
        $sinCambios = 0;
        $sinCobertura = 0;
        $totalProcesados = 0;

        $query = IneRecord::whereNotNull('latitud')->whereNotNull('longitud');
        if (!$force) {
            $query->whereNull('seccion_gps');
        }

        $query->chunkById(200, function ($records) use (&$asignados, &$sinCambios, &$sinCobertura, &$totalProcesados, $force) {
            foreach ($records as $record) {
                $totalProcesados++;
                $seccion = $this->findSeccionByPoint((float)$record->latitud, (float)$record->longitud);

                if ($seccion) {
                    if ($record->seccion_gps !== $seccion->seccion) {
                        $record->seccion_gps = $seccion->seccion;
                        $record->saveQuietly();
                        $asignados++;
                    } else {
                        $sinCambios++;
                    }
                } else {
                    $sinCobertura++;
                    if ($force && $record->seccion_gps !== null) {
                        $record->seccion_gps = null;
                        $record->saveQuietly();
                    }
                }
            }
        });

        return [
            'modo' => 'eloquent',
            'total_procesados' => $totalProcesados,
            'asignados' => $asignados,
            'sin_cambios' => $sinCambios,
            'sin_cobertura' => $sinCobertura
        ];
    }

    /**
     * Audita la consistencia entre la sección impresa en el INE y las coordenadas GPS.
     * No expone claves de elector ni coordenadas exactas por protección de datos (PII).
     */
    public function auditIneRecords(int $limit = 500): array
    {
        $coincidentes = 0;
        $discrepancias = [];
        $fueraDePoligonos = [];
        $total = 0;

        IneRecord::whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->whereNotNull('seccion')
            ->limit($limit)
            ->chunkById(200, function ($records) use (&$coincidentes, &$discrepancias, &$fueraDePoligonos, &$total) {
                foreach ($records as $record) {
                    $total++;
                    $seccionGps = $this->findSeccionByPoint((float)$record->latitud, (float)$record->longitud);

                    if (!$seccionGps) {
                        $fueraDePoligonos[] = [
                            'id' => $record->id,
                            'motivo' => 'Coordenadas fuera del polígono municipal',
                        ];
                    } elseif ($seccionGps->seccion === $record->seccion) {
                        $coincidentes++;
                    } else {
                        $discrepancias[] = [
                            'id' => $record->id,
                            'seccion_credencial' => $record->seccion,
                            'seccion_gps_real' => $seccionGps->seccion,
                            'distrito_local' => $seccionGps->distrito_local,
                        ];
                    }
                }
            });

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

    /**
     * Genera un FeatureCollection GeoJSON con métricas agregadas por sección,
     * delimitando los conteos estrictamente al alcance territorial del municipio.
     */
    public function getSeccionesGeoJsonWithMetrics(?int $municipio = null): array
    {
        $municipio = $municipio ?? $this->defaultMunicipio;

        // Traer las secciones filtradas por municipio y entidad
        $secciones = SeccionElectoral::forMunicipio($municipio)
            ->where('entidad', $this->defaultEntidad)
            ->withGeoJson()
            ->get();

        $seccionCodes = $secciones->pluck('seccion')->unique()->values();

        $metricasInes = [];
        $metricasApoyos = [];

        // Delimitar el alcance territorial de los conteos exclusivamente a las secciones del municipio
        if ($seccionCodes->isNotEmpty()) {
            $metricasInes = IneRecord::whereIn(DB::raw('COALESCE(seccion_gps, seccion)'), $seccionCodes)
                ->select(DB::raw('COALESCE(seccion_gps, seccion) as sec_code'), DB::raw('COUNT(*) as total'))
                ->groupBy(DB::raw('COALESCE(seccion_gps, seccion)'))
                ->pluck('total', 'sec_code')
                ->toArray();

            $metricasApoyos = Apoyo::whereIn('seccion', $seccionCodes)
                ->select('seccion', DB::raw('COUNT(*) as total'))
                ->groupBy('seccion')
                ->pluck('total', 'seccion')
                ->toArray();
        }

        $totalSimpatizantesMunicipio = array_sum($metricasInes);
        $totalApoyosMunicipio = array_sum($metricasApoyos);

        $features = [];

        foreach ($secciones as $sec) {
            $simpatizantes = $metricasInes[$sec->seccion] ?? 0;
            $apoyos = $metricasApoyos[$sec->seccion] ?? 0;

            $porcentajeSimpatizantes = $totalSimpatizantesMunicipio > 0
                ? round(($simpatizantes / $totalSimpatizantesMunicipio) * 100, 2)
                : 0.0;

            $porcentajeApoyos = $totalApoyosMunicipio > 0
                ? round(($apoyos / $totalApoyosMunicipio) * 100, 2)
                : 0.0;

            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'id' => $sec->id,
                    'seccion' => $sec->seccion,
                    'municipio' => $sec->municipio,
                    'distrito_federal' => $sec->distrito_federal,
                    'distrito_local' => $sec->distrito_local,
                    'tipo' => $sec->tipo,
                    'total_simpatizantes' => $simpatizantes,
                    'total_apoyos' => $apoyos,
                    'porcentaje' => $porcentajeSimpatizantes,
                    'porcentaje_simpatizantes' => $porcentajeSimpatizantes,
                    'porcentaje_apoyos' => $porcentajeApoyos,
                ],
                'geometry' => json_decode($sec->geojson)
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'summary' => [
                'total_secciones' => count($features),
                'total_simpatizantes' => $totalSimpatizantesMunicipio,
                'total_apoyos' => $totalApoyosMunicipio,
            ],
            'features' => $features
        ];
    }
}
