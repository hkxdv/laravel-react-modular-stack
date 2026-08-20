<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del módulo Examples (Tenant)
|--------------------------------------------------------------------------
|
| Rutas de login bajo guest:tenant y rutas del módulo bajo auth:tenant.
| Este módulo es un ejemplo esquelético que valida el flujo multi-usuario.
*/

use Illuminate\Support\Facades\Route;
use Modules\Examples\App\Http\Controllers\ExamplesDashboardController;
use Modules\Examples\App\Http\Controllers\TenantAuthController;

/*
| Login tenant (públicas)
*/
Route::middleware('guest:tenant')->prefix('internal/tenant')->name('tenant.')->group(
    function (): void {
        Route::get('login', [TenantAuthController::class, 'create'])->name('login');
        Route::post('login', [TenantAuthController::class, 'store'])->name('login.store');
    }
);

/*
| Dashboard del módulo Examples (protegido por guard tenant)
*/
Route::middleware([
    'auth:tenant',
    'throttle:60,1',
])->prefix('internal/tenant/examples')->name('internal.tenant.examples.')->group(
    function (): void {
        Route::get(
            '/',
            [ExamplesDashboardController::class, 'index']
        )->name('index');
    }
);

/*
| Logout tenant
*/
Route::middleware('auth:tenant')->post('internal/tenant/logout', [
    TenantAuthController::class, 'destroy',
])->name('tenant.logout');
