<?php

return [
        /*
        |--------------------------------------------------------------------------
        | SRID (Spatial Reference System Identifier)
        |--------------------------------------------------------------------------
        | Estándar WGS84 para coordenadas GPS (EPSG:4326).
        */
        'srid' => (int) env('SPATIAL_SRID', 4326),

        /*
        |--------------------------------------------------------------------------
        | Axis Order (Orden de Coordenadas)
        |--------------------------------------------------------------------------
        | MySQL 8.0 soporta axis-order. GeoJSON RFC 7946 requiere [longitud, latitud].
        */
        'axis_order' => env('SPATIAL_AXIS_ORDER', 'axis-order=long-lat'),

        /*
        |--------------------------------------------------------------------------
        | Ámbito Territorial Predeterminado
        |--------------------------------------------------------------------------
        | Entidad 28 (Tamaulipas), Municipio 041 (Ciudad Victoria).
        */
        'default_entidad'   => (int) env('SPATIAL_DEFAULT_ENTIDAD', 28),
        'default_municipio' => (int) env('SPATIAL_DEFAULT_MUNICIPIO', 41),
    ];