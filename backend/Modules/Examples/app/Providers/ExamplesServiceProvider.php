<?php

declare(strict_types=1);

namespace Modules\Examples\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Examples\App\Http\Controllers\AbstractExamplesController;
use Modules\Examples\App\Http\Controllers\ExamplesDashboardController;
use Modules\Examples\App\Http\Controllers\TenantAuthController;
use Modules\Examples\App\Models\ExampleTenantUser;
use Modules\Examples\App\ModuleConfig\ExamplesModuleConfig;
use Modules\Examples\App\PermissionRegistry\ExamplesPermissionRegistry;
use Modules\Examples\App\Policies\TenantUserPolicy;
use Modules\Examples\App\Services\ExamplesStatsService;
use Modules\Examples\App\Services\TenantUserPresenter;

/**
 * Provider principal del módulo Examples.
 * Registra y arranca los servicios necesarios del módulo.
 */
final class ExamplesServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Examples';

    private string $moduleNameLower = 'examples';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Contextual binding: Examples controllers receive TenantUserPresenter
        $this->app->when([
            AbstractExamplesController::class,
            ExamplesDashboardController::class,
            TenantAuthController::class,
        ])
            ->needs(AuthUserPresenterInterface::class)
            ->give(TenantUserPresenter::class);

        // Contextual binding para evitar colisiones globales del contrato StatsServiceInterface
        $this->app->when(AbstractExamplesController::class)
            ->needs(StatsServiceInterface::class)
            ->give(ExamplesStatsService::class);
        $this->app->when(ExamplesDashboardController::class)
            ->needs(StatsServiceInterface::class)
            ->give(ExamplesStatsService::class);
        $this->app->when(TenantAuthController::class)
            ->needs(StatsServiceInterface::class)
            ->give(ExamplesStatsService::class);

        // Permission registry: tag with 'permission-registry' for PermissionsSyncRegistry
        $this->app->tag(ExamplesPermissionRegistry::class, 'permission-registry');

        // Module config: tag with 'module-config' for ModuleConfigRegistry
        $this->app->tag(ExamplesModuleConfig::class, 'module-config');
    }

    /**
     * Arranca servicios del módulo.
     */
    public function boot(): void
    {
        Gate::policy(ExampleTenantUser::class, TenantUserPolicy::class);

        $this->registerConfig();
    }

    /**
     * Registra la configuración del módulo.
     */
    private function registerConfig(): void
    {
        $this->publishes([
            module_path(
                $this->moduleName,
                'config/config.php'
            ) => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}
