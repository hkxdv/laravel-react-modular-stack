<?php

declare(strict_types=1);

/**
 * Archivo de inicialización de la aplicación Laravel.
 * Configura la aplicación, incluyendo el enrutamiento, middleware, y manejo de excepciones.
 */

use App\Exceptions\ErrorPageResponder;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Dotenv\Dotenv;
use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Translation\FileLoader as TranslationFileLoader;
use Illuminate\Translation\Translator as TranslationTranslator;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Exceptions\UnauthorizedException;

// Verificar si se debe mostrar errores detallados de Laravel en lugar de los personalizados de Inertia
$showLaravelErrors = isset($_GET['show_laravel_errors'])
    || (bool) ($_ENV['SHOW_LARAVEL_ERRORS'] ?? false);

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(
            except: [
                'appearance',
                'sidebar_state',
            ]
        );

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
                if (
                    Illuminate\Support\Facades\Auth::guard('staff')->check()
                    && str_starts_with($request->path(), 'internal/login')
                ) {
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
    ->withProviders(require __DIR__.'/providers.php')
    ->withExceptions(function (Exceptions $exceptions) use ($showLaravelErrors) {
        // Si se solicita mostrar errores de Laravel o estamos en modo debug, no registramos los manejadores personalizados
        if ($showLaravelErrors || config('app.debug')) {
            // Configuración para mostrar errores detallados de Laravel
            $exceptions->dontReport([
                Illuminate\Auth\AuthenticationException::class,
                Illuminate\Auth\Access\AuthorizationException::class,
                Symfony\Component\HttpKernel\Exception\HttpException::class,
                Illuminate\Database\Eloquent\ModelNotFoundException::class,
                Illuminate\Validation\ValidationException::class,
            ]);

            return;
        }

        // Registro de manejadores usando la clase dedicada
        $exceptions->renderable(function (UnauthorizedException $e, $request) {
            return ErrorPageResponder::unauthorized($request);
        });

        $exceptions->renderable(function (Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            return ErrorPageResponder::http($e, $request);
        });

        $exceptions->renderable(function (Illuminate\Auth\AuthenticationException $e, $request) {
            return ErrorPageResponder::authentication($e, $request);
        });

        $exceptions->renderable(function (Illuminate\Validation\ValidationException $e, $request) {
            return ErrorPageResponder::validation($request);
        });

        $exceptions->renderable(function (Throwable $e, $request) {
            return ErrorPageResponder::generic($request);
        });
    })
    ->create();

// Establecer explícitamente la ruta de la base de datos de Laravel.
$application->useDatabasePath(__DIR__.'/../../database');

// Establecer explícitamente la ruta pública de Laravel.
$application->usePublicPath(__DIR__.'/../public');

// Bind temprano para 'cache' y 'translator' para evitar fallos en registro de paquetes
try {
    if (! $application->bound('cache')) {
        $application->singleton('cache', function ($app) {
            return new CacheManager($app);
        });
    }

    $config = $application->make('config');
    if ($config->get('cache.default') === null) {
        $config->set('cache.default', 'array');
    }
} catch (Throwable $e) {
    // Silenciar errores aquí para no bloquear el arranque; proveedores lo corregirán
}

try {
    if (! $application->bound('translator')) {
        $application->singleton(
            'translator',
            function ($app) {
                $langPath = dirname(__DIR__).'/resources/lang';
                $loader = new TranslationFileLoader(new Filesystem, $langPath);
                $locale = ($app->has('config')
                    && $app['config']->get('app.locale')) ? $app['config']->get('app.locale') : 'en';
                $translator = new TranslationTranslator($loader, $locale);
                $fallback = ($app->has('config')
                    && $app['config']->get('app.fallback_locale')) ? $app['config']->get('app.fallback_locale') : 'en';
                $translator->setFallback($fallback);

                return $translator;
            }
        );
    }
} catch (Throwable $e) {
    // Si falla, se cubrirá cuando TranslationServiceProvider se registre
}

// Determinar archivo .env según contexto (contenedor, entorno, testing)
$runningInContainer = filter_var(
    $_ENV['APP_RUNNING_IN_CONTAINER']
        ?? false,
    FILTER_VALIDATE_BOOL
);
$appEnv = $_ENV['APP_ENV'] ?? null;

if ($runningInContainer) {
    $envFile = '.env.docker';
} elseif ($appEnv === 'production') {
    $envFile = '.env.production.local';
} else {
    $envFile = '.env.local';
}

// En entorno de pruebas, no cargar archivo .env para evitar warnings si no existe
if ($appEnv !== 'testing') {
    if (file_exists(
        $application->basePath().DIRECTORY_SEPARATOR.$envFile
    )) {
        $application->loadEnvironmentFrom($envFile);
    }
}

// Cargar variables adicionales desde .env.users en la raíz del monorepo
try {
    $usersEnvBase = dirname($application->basePath());
    $usersEnvFile = '.env.users';
    if (file_exists(
        $usersEnvBase.DIRECTORY_SEPARATOR.$usersEnvFile
    )) {
        Dotenv::createMutable(
            $usersEnvBase,
            $usersEnvFile
        )->safeLoad();
    }
} catch (Throwable $e) {
    // Ignorar cualquier error al cargar .env.users
}

return $application;
