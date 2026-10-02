<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Adapters\XlsxToCsvAdapter;
use App\Contracts\RowValidatorInterface;
use App\Contracts\RowReaderInterface;
use App\Services\Readers\AdaptiveRowReader;
use App\Services\Readers\SplCsvRowReader;
use App\Validators\ListaNominalRowValidator\RowValidator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RowValidatorInterface::class, RowValidator::class);

        $this->app->bind(RowReaderInterface::class, function () {
            return new AdaptiveRowReader(
                csvReader: new SplCsvRowReader(),
                adapters: [
                    new XlsxToCsvAdapter(),
                ]
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
    }
}
