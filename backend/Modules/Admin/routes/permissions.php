<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\Permissions\ListPermissionsController;

/**
 * Grupo de rutas para la consulta de permisos (solo lectura).
 * Prefijo de URL: '/internal/staff/admin/permissions'
 * Prefijo de Nombre: 'internal.staff.admin.permissions.'
 */
Route::prefix('permissions')->name('permissions.')->group(
    function (): void {
        // Muestra la lista de permisos agrupados por módulo.
        Route::get('/', [ListPermissionsController::class, 'index'])
            ->middleware('permission:permissions.view,staff')
            ->name('index');
    }
);
