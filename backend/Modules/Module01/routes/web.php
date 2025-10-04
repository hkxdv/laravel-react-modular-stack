<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas Web del Módulo 01
|--------------------------------------------------------------------------
|
| Todas las rutas están prefijadas con '/internal/module-01' y protegidas
| por el guard 'staff' y el permiso base del módulo.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Module01\App\Http\Controllers\Module01PanelController;

Route::middleware(['auth:staff', 'verified', 'throttle:60,1', 'permission:access-module-01,staff'])
    ->prefix('internal/module-01')
    ->name('internal.module01.')
    ->group(function () {
        // Panel principal del módulo (ruta base)
        Route::get('/', [Module01PanelController::class, 'showModulePanel'])->name('index');
    });
