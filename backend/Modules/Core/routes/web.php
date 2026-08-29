<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Auth\LoginChallengeController;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile\AccountSecurityController;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile\AppearanceController;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile\NotificationPreferencesController;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile\PasswordController;
use Modules\Core\Infrastructure\Laravel\Http\Controllers\Profile\ProfileController;

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

/**
 * Grupo de rutas para la configuración del perfil del personal.
 */
Route::prefix('internal/staff')
    ->name('internal.staff.')
    ->middleware(['auth:staff', 'verified', 'throttle:60,1'])
    ->group(function (): void {
        /**
         * Redirige la ruta base '/internal/staff' a la página de perfil.
         * GET /internal/staff -> /internal/staff/profile
         */
        Route::redirect('/', 'profile')->name('root.redirect');

        // --- Gestión del Perfil ---
        Route::controller(ProfileController::class)->group(function (): void {
            Route::get('profile', 'edit')->name('profile.edit');
            Route::patch('profile', 'update')
                ->middleware('throttle:6,1')->name('profile.update');
            Route::delete('profile', 'destroy')
                ->middleware('throttle:6,1')->name('profile.destroy');
        });

        // --- Gestión de Contraseña ---
        Route::controller(PasswordController::class)->group(function (): void {
            Route::get('password', 'edit')->name('password.edit');
            Route::put('password', 'update')
                ->middleware('throttle:6,1')->name('password.update');
        });

        // --- Configuración de Apariencia ---
        Route::controller(AppearanceController::class)->group(function (): void {
            Route::get('appearance', 'show')->name('appearance');
        });

        // --- Seguridad de Cuenta ---
        Route::controller(AccountSecurityController::class)->group(function (): void {
            Route::get('security', 'edit')->name('security.edit');
            Route::post('security/sessions/revoke', 'revokeOtherSessions')
                ->middleware('throttle:6,1')->name('security.sessions.revoke');
            Route::post('security/2fa/setup', 'setupTwoFactor')
                ->middleware('throttle:6,1')->name('security.two-factor.setup');
            Route::post('security/2fa/confirm', 'confirmTwoFactor')
                ->middleware('throttle:6,1')->name('security.two-factor.confirm');
            Route::delete('security/2fa', 'disableTwoFactor')
                ->middleware('throttle:6,1')->name('security.two-factor.disable');
            Route::post('security/2fa/recovery-codes', 'regenerateRecoveryCodes')
                ->middleware('throttle:6,1')->name('security.two-factor.recovery-codes');
        });

        // --- Preferencias de Notificaciones ---
        Route::controller(NotificationPreferencesController::class)->group(function (): void {
            Route::get('notifications', 'edit')->name('notifications.edit');
            Route::patch('notifications', 'update')
                ->middleware('throttle:6,1')->name('notifications.update');
        });
    });
