<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SeccionElectoral extends Model
{
    use HasFactory;

    protected $table = 'secciones_electorales';

    protected $fillable = [
        'entidad',
        'municipio',
        'seccion',
        'distrito_federal',
        'distrito_local',
        'tipo',
        'control',
        'poligono',
    ];

    protected $casts = [
        'entidad'          => 'integer',
        'municipio'        => 'integer',
        'distrito_federal' => 'integer',
        'distrito_local'   => 'integer',
        'tipo'             => 'integer',
        'control'          => 'integer',
    ];

    protected $hidden = [
        'poligono',
    ];

    /**
     * Relación atributiva directa con la INE capturada.
     */
    public function ineRecord(): HasMany
    {
        return $this->hasMany(IneRecord::class, 'seccion', 'seccion');
    }

    public function ineRecords(): HasMany
    {
        return $this->hasMany(IneRecord::class, 'seccion', 'seccion');
    }

    /**
     * Scope para traer el polígono convertido a GeoJSON directamente de MySQL.
     */
    public function scopeSelectGeoJson($query)
    {
        return $query->addSelect([
            'id',
            'entidad',
            'municipio',
            'seccion',
            'distrito_federal',
            'distrito_local',
            'tipo',
            'control',
            DB::raw('ST_AsGeoJson(poligono) as geojson')
        ]);
    }

    public function scopeWithGeoJson($query)
    {
        return $this->scopeSelectGeoJson($query);
    }

    /**
     * Scope geoespacial: Determinar en qué sección electoral cae un punto GPS (Point in Polygon).
     *
     * IMPORTANTE: En MySQL 8.0 con SRID 4326 y 'axis-order=long-lat', el formato de POINT WKT es (longitud latitud).
     */
    public function scopeContainingPoint(
    $query,
    float $latitud,
    float $longitud
) {
    $point = "POINT({$longitud} {$latitud})";

    return $query->whereRaw(
        "ST_Contains(
            poligono,
            ST_GeomFromText(?, 4326)
        )",
        [$point]
    );
}

    /**
     * Scope para filtrar por municipio (por defecto 41 - Victoria).
     */
    public function scopeForMunicipio($query, int $municipio = 41)
    {
        return $query->where('municipio', $municipio);
    }
}
