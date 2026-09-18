<?php

namespace App\Contracts;

use App\Models\SeccionElectoral;

interface SpatialServiceInterface
{
    /**
     * Determina la sección electoral que contiene un punto GPS (Point in Polygon).
     */
    public function findSeccionByPoint(float $latitud, float $longitud): ?SeccionElectoral;

    /**
     * Auto-asigna la sección territorial a los registros de apoyos pendientes.
     */
    public function assignSeccionToApoyos(bool $useBulkSql = false): array;

    /**
     * Audita la consistencia entre la sección impresa en el INE y la coordenada GPS.
     */
    public function auditIneRecords(int $limit = 500): array;

    /**
     * Genera un FeatureCollection GeoJSON con métricas agregadas por sección.
     */
    public function getSeccionesGeoJsonWithMetrics(?int $municipio = null): array;
}
