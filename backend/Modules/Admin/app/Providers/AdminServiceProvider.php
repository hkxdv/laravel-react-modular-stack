<?php

declare(strict_types=1);

namespace Modules\Admin\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Admin\App\Http\Controllers\AdminDashboardController;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Admin\App\Models\StaffUser;
use Modules\Admin\App\ModuleConfig\AdminModuleConfig;
use Modules\Admin\App\PermissionRegistry\AdminPermissionRegistry;
use Modules\Admin\App\Policies\PermissionPolicy;
use Modules\Admin\App\Policies\RolePolicy;
use Modules\Admin\App\Policies\StaffUserPolicy;
use Modules\Admin\App\Services\AdminStaffUserService;
use Modules\Admin\App\Services\AdminStatsService;
use Modules\Admin\App\Services\RoleService;
use Modules\Admin\App\Services\StaffUserPresenter;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Contracts\StatsServiceInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Provider principal del módulo Admin.
 * Registra y arranca todos los servicios específicos del módulo.
 */
final class AdminServiceProvider extends ServiceProvider
{
    private string $moduleName = 'Admin';

    private string $moduleNameLower = 'admin';

    /**
     * Registra servicios, bindings y comandos del módulo.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        // Registrar la implementación de StaffUserManagerInterface
        $this->app->bind(
            StaffUserManagerInterface::class,
            AdminStaffUserService::class
        );

        // Registrar la implementación de RolesInterface
        $this->app->bind(
            RolesInterface::class,
            RoleService::class
        );

        // Contextual binding: Admin controllers receive StaffUserPresenter
        $this->app->when([
            AdminDashboardController::class,
        ])
            ->needs(AuthUserPresenterInterface::class)
            ->give(StaffUserPresenter::class);

        $this->app->when(AdminDashboardController::class)
            ->needs(StatsServiceInterface::class)
            ->give(AdminStatsService::class);

        $this->app->when(RoleService::class)
            ->needs(PermissionVerifierInterface::class)
            ->give(fn () => resolve(PermissionVerifierInterface::class));

        $this->app->when(AdminStaffUserService::class)
            ->needs(PermissionVerifierInterface::class)
            ->give(fn () => resolve(PermissionVerifierInterface::class));

        // Permission registry: tag with 'permission-registry' for PermissionsSyncRegistry
        $this->app->tag(AdminPermissionRegistry::class, 'permission-registry');

        // Module config: tag with 'module-config' for ModuleConfigRegistry
        $this->app->tag(AdminModuleConfig::class, 'module-config');
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerPolicies();
    }

    /**
     * Registra policies de autorización para nWidart (sin auto-discovery).
     */
    private function registerPolicies(): void
    {
        Gate::policy(StaffUser::class, StaffUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
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
            module_path(
                $this->moduleName,
                'config/config.php'
            ),
            $this->moduleNameLower
        );
    }
}
