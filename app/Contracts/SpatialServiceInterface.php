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
     * Auto-asigna o sincroniza la sección territorial a los registros de simpatizantes INE.
     */
    public function assignSeccionToIneRecords(bool $useBulkSql = false, bool $force = true): array;

    /**
     * Audita la consistencia entre la sección impresa en el INE y la coordenada GPS.
     */
    public function auditIneRecords(int $limit = 500): array;

    /**
     * Genera un FeatureCollection GeoJSON con métricas agregadas por sección.
     */
    public function getSeccionesGeoJsonWithMetrics(?int $municipio = null): array;

    /**
     * Configura el motor de base de datos para consultas geoespaciales ('mysql', 'mariadb', 'auto').
     */
    public function setEngine(string $engine): self;

    /**
     * Obtiene el motor actual configurado o detectado.
     */
    public function getEngine(): string;

    /**
     * Indica si el motor activo se comporta como MariaDB (sin axis-order en ST_GeomFromText).
     */
    public function isMariaDb(): bool;
}
