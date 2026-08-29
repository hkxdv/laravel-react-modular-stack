<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Models\StaffUser;

/**
 * Proveedor de servicios para la configuración de rutas.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define los enlaces de modelos de ruta, filtros de patrones y otra configuración de rutas.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');
        Route::pattern('slug', '[a-z0-9-]+');
        Route::model('staffUser', StaffUser::class);

        $this->configureRateLimiting();

        $this->routes(function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configura los limitadores de velocidad para la aplicación.
     */
    private function configureRateLimiting(): void
    {
        // Límite general para la API: 60 solicitudes por minuto.
        RateLimiter::for(
            'api',
            fn (Request $request) => Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
        );

        // Límite para intentos de autenticación: 5 por minuto por IP.
        RateLimiter::for(
            'auth',
            fn (Request $request) => Limit::perMinute(5)->by($request->ip())
        );

        // Límite para operaciones con Sanctum (ej. emisión de tokens).
        RateLimiter::for(
            'sanctum',
            fn (Request $request) => Limit::perMinute(10)->by($request->ip())
        );
    }
}
