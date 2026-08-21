<?php

declare(strict_types=1);

namespace Modules\Admin\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * Provider para el registro de rutas del módulo Admin.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define las rutas del módulo.
     */
    public function boot(): void
    {
        Route::bind(
            'role',
            fn ($value) => Role::query()->findOrFail($value)
        );

        $this->routes(function (): void {
            Route::middleware('web')
                ->group(module_path('Admin', 'routes/web.php'));
        });
    }
}
