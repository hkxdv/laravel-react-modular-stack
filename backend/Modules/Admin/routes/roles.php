<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\Roles\CreateRoleController;
use Modules\Admin\App\Http\Controllers\Roles\DeleteRoleController;
use Modules\Admin\App\Http\Controllers\Roles\EditRoleController;
use Modules\Admin\App\Http\Controllers\Roles\ListRolesController;

/**
 * Grupo de rutas para la gestión de roles (CRUD).
 * Prefijo de URL: '/internal/staff/admin/roles'
 * Prefijo de Nombre: 'internal.staff.admin.roles.'
 */
Route::prefix('roles')->name('roles.')->group(
    function (): void {
        // Muestra la lista de roles.
        Route::get('/', [ListRolesController::class, 'index'])
            ->middleware('permission:roles.view,staff')
            ->name('index');

        // Muestra el formulario para crear un nuevo rol.
        Route::get('/create', [CreateRoleController::class, 'create'])
            ->middleware('permission:roles.create,staff')
            ->name('create');

        // Almacena el nuevo rol.
        Route::post('/', [CreateRoleController::class, 'store'])
            ->middleware('permission:roles.create,staff')
            ->name('store');

        // Muestra el formulario para editar un rol existente.
        Route::get('/{role}/edit', [EditRoleController::class, 'edit'])
            ->middleware('permission:roles.update,staff')
            ->name('edit');

        // Actualiza el rol existente.
        Route::put('/{role}', [EditRoleController::class, 'update'])
            ->middleware('permission:roles.update,staff')
            ->name('update');

        // Elimina el rol.
        Route::delete('/{role}', [DeleteRoleController::class, 'destroy'])
            ->middleware('permission:roles.delete,staff')
            ->name('destroy');
    }
);
