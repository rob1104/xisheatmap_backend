<?php

namespace App\Providers;

use App\Adapters\XlsxToCsvAdapter;
use App\Contracts\RowReaderInterface;
use App\Contracts\RowValidatorInterface;
use App\Policies\RolePolicy;
use App\Services\Readers\AdaptiveRowReader;
use App\Services\Readers\SplCsvRowReader;
use App\Validators\ListaNominalRowValidator\RowValidator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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

        Gate::policy(Role::class, RolePolicy::class);
    }
}