<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzamos HTTPS si la URL de la app empieza por https o si estamos en producción
        if (str_contains(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }
    }
}
