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

    /**
     * Relación atributiva: Una sección tiene muchos registros de credenciales INE.
     */
    public function ineRecords(): HasMany
    {
        return $this->hasMany(IneRecord::class, 'seccion', 'seccion');
    }

    /**
     * Scope para seleccionar el polígono convertido en GeoJSON directamente desde MySQL.
     */
    public function scopeWithGeoJson($query)
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
            DB::raw('ST_AsGeoJSON(poligono) as geojson')
        ]);
    }

    /**
     * Scope geoespacial (Point in Polygon):
     * Encuentra la sección electoral que contiene las coordenadas GPS dadas.
     *
     * IMPORTANTE: En MySQL 8.0 con SRID 4326 y 'axis-order=long-lat', el formato de POINT es (longitud latitud).
     */
    public function scopeContainingPoint($query, float $latitud, float $longitud)
    {
        return $query->whereRaw(
            "ST_Contains(poligono, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))",
            ["POINT({$longitud} {$latitud})"]
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
