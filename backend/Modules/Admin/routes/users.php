<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\StaffUsers\CreateStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\DeleteStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\EditStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\ListStaffUsersController;

/**
 * Grupo de rutas para la gestión de usuarios (CRUD de vistas).
 * Prefijo de URL: '/internal/staff/admin/users'
 * Prefijo de Nombre: 'internal.staff.admin.users.'
 */
Route::prefix('users')->name('users.')->group(
    function (): void {
        // Muestra la lista de usuarios.
        Route::get('/', [ListStaffUsersController::class, 'index'])
            ->middleware('permission:staff-users.view,staff')
            ->name('index');

        // Muestra el formulario para crear un nuevo usuario.
        Route::get('/create', [
            CreateStaffUserController::class,
            'create',
        ])->middleware('permission:staff-users.create,staff')
            ->name('create');

        // Almacena el nuevo usuario.
        Route::post('/', [
            CreateStaffUserController::class,
            'store',
        ])->middleware('permission:staff-users.create,staff')
            ->name('store');

        // Muestra el formulario para editar un usuario existente.
        Route::get('/{user}/edit', [
            EditStaffUserController::class,
            'edit',
        ])->middleware('permission:staff-users.update,staff')
            ->name('edit');

        // Actualiza el usuario existente.
        Route::put('/{user}', [
            EditStaffUserController::class,
            'update',
        ])->middleware('permission:staff-users.update,staff')
            ->name('update');

        // Elimina el usuario.
        Route::delete('/{user}', [
            DeleteStaffUserController::class,
            'destroy',
        ])->middleware('permission:staff-users.delete,staff')
            ->name('destroy');
    }
);
