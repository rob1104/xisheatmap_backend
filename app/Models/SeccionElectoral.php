<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SeccionElectoral extends Model 
{
    protected $table = 'seccion_electorales';

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

    // Relacion atributiba directa con la INE capturada

    public function ineRecord(): HasMany
    {
        return $this->hasMany(IneRecord::class,'seccion','seccion');
    }

    // Scope para traer el poligono convertido a GeoJson directamente de MySQL

    public function scopeSelectGeoJson($query)
    {
        return $query->addSelect([
            'id',
            'entidad',
            'seccion',
            'distrito_federal',
            'distrito_local',
            'tipo',
            DB::raw('ST_AsGeoJson(poligono) as geojson')
        ]);
    }
    
    // Scope geospacial: Determinar en que sección electoral cae un punto GPS (Point in Polygon)

    public function scopeContainingPoint($query, float $latitud, float $longitud)
    {
        return $query->whereRaw(
            "ST_Contains(poligono, ST_GeomFromText('POINT(? ?)', 4326, 'axis-order=long-lat'))",
            [$longitud, $latitud]
        );
    }

}