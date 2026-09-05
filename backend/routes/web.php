<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Ruta principal de bienvenida
Route::get(
    '/',
    fn () => inertia('public/welcome')
)->name('welcome');

/**
 * Redirige la antigua ruta de registro a la página de inicio de sesión.
 * El registro de personal se maneja internamente.
 * GET /register
 */
Route::get(
    '/register',
    fn (): RedirectResponse => to_route('login')
)->name('register.redirect');

/**
 * Ruta para obtener la cookie CSRF, necesaria para clientes SPA como Vue/React.
 * GET /sanctum/csrf-cookie
 */
Route::get(
    '/sanctum/csrf-cookie',
    fn () => response()->noContent()
)->name('sanctum.csrf-cookie');

/*
|--------------------------------------------------------------------------
| Carga de Archivos de Rutas Adicionales
|--------------------------------------------------------------------------
|
| Se usa `require` y no `require_once`: web.php se vuelve a incluir en cada
| re-bootstrap de la aplicación (tests/Pest), y `require_once` devolvería
| `true` sin re-registrar las rutas (ver nota en bootstrap/app.php).
*/
require sprintf('%s/internal.php', __DIR__);
require sprintf('%s/protect-assets.php', __DIR__);
require sprintf('%s/profile.php', __DIR__);

// Descubrimiento estándar de endpoints de passkeys.
Route::get('.well-known/passkey-endpoints', fn (): JsonResponse => response()->json([
    'enroll' => route('internal.staff.security.edit'),
    'manage' => route('internal.staff.security.edit'),
]))->name('well-known.passkeys');
