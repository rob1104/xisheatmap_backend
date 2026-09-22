<?php

namespace App\Facades;

use App\Contracts\SpatialServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\SeccionElectoral|null findSeccionByPoint(float $latitud, float
 $longitud)
    * @method static array assignSeccionToApoyos(bool $useBulkSql = false)
    * @method static array auditIneRecords(int $limit = 500)
    * @method static array getSeccionesGeoJsonWithMetrics(?int $municipio = null)
    *
    * @see \App\Services\SpatialService
    */
class Spatial extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SpatialServiceInterface::class;
    }
}