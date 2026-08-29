<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\ApiResponseFormatterInterface;
use App\Services\ApiResponseService;
use App\Services\AuthUserPresenterResolver;
use App\Services\JsonbQueryService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterResolverInterface;
use Modules\Examples\App\Models\ExampleTenantUser;

/**
 * Proveedor de servicios principal de la aplicación
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de la aplicación en el contenedor de dependencias.
     */
    public function register(): void
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = $this->app;

        // Establece una ruta personalizada para la base de datos.
        $app->useDatabasePath(base_path('../database'));

        // Registra Telescope solo en entornos de no producción para depuración.
        if (! $this->app->environment('production')) {
            $this->app->register(
                \Laravel\Telescope\TelescopeServiceProvider::class
            );
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Registrar las interfaces del sistema con sus implementaciones concretas.
        $this->app->singleton(
            ApiResponseFormatterInterface::class,
            ApiResponseService::class
        );
        $this->app->singleton(JsonbQueryService::class);

        // Resolución de presenter de usuario según guard, implementada en el
        // shell app/ (puente entre módulos que Core no debe conocer).
        $this->app->singleton(
            AuthUserPresenterResolverInterface::class,
            AuthUserPresenterResolver::class
        );
    }

    /**
     * Arranca los servicios de la aplicación después de que se hayan registrado.
     */
    public function boot(): void
    {
        // Register morph map for Spatie permission pivot tables.
        Relation::morphMap([
            'staff-user' => StaffUser::class,
            'tenant-user' => ExampleTenantUser::class,
        ]);
    }
}
