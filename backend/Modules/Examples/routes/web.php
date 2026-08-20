<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 01
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/staff/examples' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Examples\App\Http\Controllers\ExamplesDashboardController;

/**
 * Grupo principal de rutas para el Módulo 01.
 * Prefijo de URL: '/internal/staff/examples'
 * Prefijo de Nombre de Ruta: 'internal.staff.examples.'
 * Middleware base: 'auth', 'verified'
 */
Route::middleware([
    'auth:staff',
    'verified',
    'throttle:60,1',
    'permission:access-examples,staff',
])->prefix('internal/staff/examples')->name('internal.staff.examples.')->group(
    function (): void {
        /**
         * Muestra el panel principal del Módulo 01.
         * URL: /internal/staff/examples
         * Nombre de Ruta: internal.staff.examples.index
         * Controlador: ExamplesDashboardController@index
         * Permiso Requerido: access-examples
         */
        Route::get(
            '/',
            [ExamplesDashboardController::class, 'index']
        )->name('index');
    }
);
