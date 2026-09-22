<?php

namespace App\Providers;

use App\Contracts\SpatialServiceInterface;
use App\Services\SpatialService;
use Illuminate\Support\ServiceProvider;

class SpatialServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom( __DIR__.'/../../config/spatial.php', 'spatial');

        $this->app->singleton(SpatialServiceInterface::class, function ($app) {
            return new SpatialService(
                srid: (int) config('spatial.srid', 4326),
                axisOrder: (string) config('spatial.axis_order', 'axis-order=long-lat'),
                defaultEntidad: (int) config('spatial.default_entidad', 28),
                defaultMunicipio: (int) config('spatial.default_municipio', 41),
                engine: (string) config('spatial.engine', 'auto')
            );
        });

        $this->app->alias(SpatialServiceInterface::class, 'spatial');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
       //
    }
}
