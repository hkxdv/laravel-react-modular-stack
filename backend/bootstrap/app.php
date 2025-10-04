<?php

declare(strict_types=1);

/**
 * Archivo de inicialización de la aplicación Laravel.
 * Configura la aplicación, incluyendo el enrutamiento, middleware, y manejo de excepciones.
 */

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Exceptions\UnauthorizedException;

ini_set('memory_limit', '512M');
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
$timezone = $_ENV['APP_TIMEZONE'] ?? 'UTC';
date_default_timezone_set($timezone);

// Verificar si se debe mostrar errores detallados de Laravel en lugar de los personalizados de Inertia
$showLaravelErrors = isset($_GET['show_laravel_errors']) || (bool) ($_ENV['SHOW_LARAVEL_ERRORS'] ?? false);

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'permission' => CheckPermission::class,
        ]);

        // Definir ruta de redirección para usuarios no autenticados
        $middleware->redirectGuestsTo(
            function ($request) {
                // Si el usuario ya está autenticado en el guard de staff, no debería ver páginas de login internas
                if (\Illuminate\Support\Facades\Auth::guard('staff')->check() && str_starts_with($request->path(), 'internal/login')) {
                    return route('internal.dashboard');
                }

                // Si no está autenticado, redirigir según la ruta
                if (str_starts_with($request->path(), 'internal')) {
                    return route('login');
                }

                return route('welcome');
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions) use ($showLaravelErrors) {
        // Si se solicita mostrar errores de Laravel o estamos en modo debug, no registramos los manejadores personalizados
        if ($showLaravelErrors || config('app.debug')) {
            // Configuración para mostrar errores detallados de Laravel
            $exceptions->dontReport([
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
                \Symfony\Component\HttpKernel\Exception\HttpException::class,
                \Illuminate\Database\Eloquent\ModelNotFoundException::class,
                \Illuminate\Validation\ValidationException::class,
            ]);

            return;
        }

        // Mensajes amigables para códigos de error comunes
        $errorMessages = [
            400 => 'La solicitud contiene errores o no puede ser procesada.',
            401 => 'No has iniciado sesión o tu sesión ha expirado.',
            403 => 'No tienes permiso para acceder a esta página.',
            404 => 'Lo sentimos, la página que buscas no existe.',
            405 => 'El método de solicitud no está permitido.',
            408 => 'La solicitud tardó demasiado tiempo en completarse.',
            419 => 'Tu sesión ha expirado. Por favor, recarga la página e intenta nuevamente.',
            422 => 'Los datos proporcionados no son válidos. Por favor, verifica la información.',
            429 => 'Has realizado demasiadas solicitudes en poco tiempo. Por favor, espera un momento.',
            500 => 'Se ha producido un error interno en el servidor.',
            503 => 'El servicio no está disponible temporalmente. Por favor, intenta de nuevo más tarde.',
        ];

        // Función para obtener mensajes de error amigables
        $getErrorMessage = function (int $status, ?string $message = null) use ($errorMessages) {
            // Si hay un mensaje específico y no estamos en producción, mostrarlo
            if ($message && !app()->isProduction()) {
                return $message;
            }

            // Usar el mensaje predefinido o un mensaje genérico
            return $errorMessages[$status] ?? 'Se ha producido un error inesperado.';
        };

        // 1. Manejo de excepciones de autorización de Spatie
        $exceptions->renderable(function (UnauthorizedException $e, $request) use ($getErrorMessage) {
            return Inertia::render('errors/error-page', [
                'status' => 403,
                'message' => $getErrorMessage(403),
            ])
                ->toResponse($request)
                ->setStatusCode(403);
        });

        // 2. Manejo de errores HTTP genéricos
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($getErrorMessage) {
            $status = $e->getStatusCode();

            // Usar una única página de error para todos los códigos de estado
            return Inertia::render('errors/error-page', [
                'status' => $status,
                'message' => $getErrorMessage($status, $e->getMessage()),
            ])->toResponse($request)->setStatusCode($status);
        });

        // 3. Manejo de errores de autenticación para peticiones API
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return null;
        });

        // 4. Manejo de excepciones de validación
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) use ($getErrorMessage) {
            return Inertia::render('errors/error-page', [
                'status' => 422,
                'message' => $getErrorMessage(422),
            ])->toResponse($request)->setStatusCode(422);
        });

        // 5. Manejo de errores generales (500)
        $exceptions->renderable(function (\Throwable $e, $request) use ($getErrorMessage) {
            if (!config('app.debug')) {
                return Inertia::render('errors/error-page', [
                    'status' => 500,
                    'message' => $getErrorMessage(500),
                ])->toResponse($request)->setStatusCode(500);
            }

            return null;
        });
    })
    ->create();

// Establecer explícitamente la ruta de la base de datos de Laravel.
$application->useDatabasePath(__DIR__ . '/../../database');

// Establecer explícitamente la ruta pública de Laravel.
$application->usePublicPath(__DIR__ . '/../public');

$isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';

// Configuración adicional para entornos de producción
if ($isProduction) {
    // Desactivar output buffering en producción
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Ocultar errores para usuarios finales
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');

    // Forzar HTTPS en producción solo si se habilita explícitamente
    if (filter_var($_ENV['APP_FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOL)) {
        URL::forceScheme('https');
    }
}

// Determinar archivo .env según contexto (contenedor, entorno, testing) con fallback a .env
$runningInContainer = filter_var($_ENV['APP_RUNNING_IN_CONTAINER'] ?? false, FILTER_VALIDATE_BOOL);
$appEnv = $_ENV['APP_ENV'] ?? null;

if ($runningInContainer) {
    $envFile = '.env.docker';
} elseif ($appEnv === 'production') {
    $envFile = '.env.production.local';
} elseif ($appEnv === 'testing') {
    // En entorno de pruebas, usar .env por defecto para evitar warnings si .env.local no existe
    $envFile = '.env';
} else {
    $envFile = '.env.local';
}

// Fallback a .env si el archivo elegido no existe
$basePath = dirname(__DIR__);
if (!file_exists($basePath . DIRECTORY_SEPARATOR . $envFile)) {
    $envFile = '.env';
}

// En entorno de pruebas, no cargar archivo .env para evitar warnings si no existe
if ($appEnv !== 'testing') {
    if (file_exists($basePath . DIRECTORY_SEPARATOR . $envFile)) {
        $application->loadEnvironmentFrom($envFile);
    }
}

return $application;
