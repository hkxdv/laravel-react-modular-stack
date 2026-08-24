<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\App\Http\Controllers\AdminDashboardController;
use Modules\Admin\App\Http\Controllers\Permissions\ListPermissionsController;
use Modules\Admin\App\Http\Controllers\Roles\CreateRoleController;
use Modules\Admin\App\Http\Controllers\Roles\DeleteRoleController;
use Modules\Admin\App\Http\Controllers\Roles\EditRoleController;
use Modules\Admin\App\Http\Controllers\Roles\ListRolesController;
use Modules\Admin\App\Http\Controllers\StaffUsers\CreateStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\DeleteStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\EditStaffUserController;
use Modules\Admin\App\Http\Controllers\StaffUsers\ListStaffUsersController;

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo de Administración
|--------------------------------------------------------------------------
|
| Aquí se definen las rutas para el panel de administración y la gestión
| de usuarios del personal (Staff). Todas las rutas están protegidas por
| el guard 'staff' y permisos específicos granulares.
|
*/

Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:rbac.view,staff',
])->prefix('internal/staff/admin')->name('internal.staff.admin.')->group(
    function (): void {

        // La ruta principal del panel de administración.
        // GET /internal/staff/admin
        Route::get(
            '/',
            [AdminDashboardController::class, 'index']
        )->name('index');

        // Grupo de rutas para la gestión de usuarios (CRUD de vistas).
        // Prefijo de URL: '/internal/staff/admin/users'
        // Prefijo de Nombre: 'internal.staff.admin.users.'
        Route::prefix('users')->name('users.')->group(
            function (): void {
                Route::get('/', [ListStaffUsersController::class, 'index'])
                    ->middleware('permission:staff-users.view,staff')
                    ->name('index');

                Route::get('/create', [CreateStaffUserController::class, 'create'])
                    ->middleware('permission:staff-users.create,staff')
                    ->name('create');

                Route::post('/', [CreateStaffUserController::class, 'store'])
                    ->middleware('permission:staff-users.create,staff')
                    ->name('store');

                Route::get('/{staffUser}/edit', [EditStaffUserController::class, 'edit'])
                    ->middleware('permission:staff-users.update,staff')
                    ->name('edit');

                Route::put('/{staffUser}', [EditStaffUserController::class, 'update'])
                    ->middleware('permission:staff-users.update,staff')
                    ->name('update');

                Route::delete('/{staffUser}', [DeleteStaffUserController::class, 'destroy'])
                    ->middleware('permission:staff-users.delete,staff')
                    ->name('destroy');
            }
        );

        // Grupo de rutas para la gestión de roles (CRUD).
        // Prefijo de URL: '/internal/staff/admin/roles'
        // Prefijo de Nombre: 'internal.staff.admin.roles.'
        Route::prefix('roles')->name('roles.')->group(
            function (): void {
                Route::get('/', [ListRolesController::class, 'index'])
                    ->middleware('permission:roles.view,staff')
                    ->name('index');

                Route::get('/create', [CreateRoleController::class, 'create'])
                    ->middleware('permission:roles.create,staff')
                    ->name('create');

                Route::post('/', [CreateRoleController::class, 'store'])
                    ->middleware('permission:roles.create,staff')
                    ->name('store');

                Route::get('/{role}/edit', [EditRoleController::class, 'edit'])
                    ->middleware('permission:roles.update,staff')
                    ->name('edit');

                Route::put('/{role}', [EditRoleController::class, 'update'])
                    ->middleware('permission:roles.update,staff')
                    ->name('update');

                Route::delete('/{role}', [DeleteRoleController::class, 'destroy'])
                    ->middleware('permission:roles.delete,staff')
                    ->name('destroy');
            }
        );

        // Grupo de rutas para la consulta de permisos (solo lectura).
        // Prefijo de URL: '/internal/staff/admin/permissions'
        // Prefijo de Nombre: 'internal.staff.admin.permissions.'
        Route::prefix('permissions')->name('permissions.')->group(
            function (): void {
                Route::get('/', [ListPermissionsController::class, 'index'])
                    ->middleware('permission:permissions.view,staff')
                    ->name('index');
            }
        );
    }
);
