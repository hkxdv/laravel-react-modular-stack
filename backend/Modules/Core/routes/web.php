<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Auth\LoginChallengeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/**
 * Challenge de 2FA durante el login (accesible sin sesión autenticada:
 * el usuario tiene credenciales válidas pero 2FA pendiente).
 */
Route::middleware('guest:staff')->group(function (): void {
    Route::get('two-factor-challenge', [LoginChallengeController::class, 'show'])
        ->name('security.two-factor-challenge');
    Route::post('two-factor-challenge', [LoginChallengeController::class, 'verify'])
        ->middleware('throttle:6,1')->name('security.two-factor-challenge.verify');
});
