<?php

declare(strict_types=1);

/**
 * Archivo de inicialización de la aplicación Laravel.
 * Configura la aplicación, incluyendo el enrutamiento, middleware, y manejo de excepciones.
 */

// Verificar si se debe mostrar errores detallados de Laravel en lugar de los personalizados de Inertia
// Importante: Evitar usar Facades antes de que la aplicación esté creada; capturamos la Request directamente.
$showLaravelErrors = Illuminate\Http\Request::capture()->query('show_laravel_errors') !== null
  || (bool) (Illuminate\Support\Env::get('SHOW_LARAVEL_ERRORS', false));

/**
 * NOTA: Intencionalmente usamos `require` y no `require_once`.
 * Estos archivos devuelven callables/arrays que deben capturarse cada vez
 * que app.php se ejecuta. `require_once` devolveria `true` en re-bootstraps
 * de tests (Pest) y causaria "Value of type bool is not callable".
 */

/** @var callable $middlewareConfigurator */
$middlewareConfigurator = require __DIR__.'/modules/middleware.php';

$providers = (array) (require __DIR__.'/providers.php');

/** @var callable $exceptionsConfiguratorFactory */
$exceptionsConfiguratorFactory = require __DIR__.'/modules/exceptions.php';

/** @var callable $exceptionsConfigurator */
$exceptionsConfigurator = $exceptionsConfiguratorFactory($showLaravelErrors);

$application = Illuminate\Foundation\Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware($middlewareConfigurator)
    ->withProviders($providers)
    ->withExceptions($exceptionsConfigurator)
    ->create();

// El monorepo almacena los archivos de entorno en <repo-root>/.envs/, no en backend/.
// Configura el cargador de variables de entorno predeterminado de Laravel para que apunte allí;
// así encontrará .env.testing (seleccionado mediante APP_ENV) en lugar de emitir una advertencia
// por la ausencia de backend/.env.
$envsPath = dirname($application->basePath()).DIRECTORY_SEPARATOR.'.envs';
$application->useEnvironmentPath($envsPath);

/** @var callable $pathsBootstrap */
$pathsBootstrap = require __DIR__.'/modules/paths.php';
$pathsBootstrap($application);

/** @var callable $bindingsBootstrap */
$bindingsBootstrap = require __DIR__.'/modules/bindings.php';
$bindingsBootstrap($application);

/** @var callable $envBootstrap */
$envBootstrap = require __DIR__.'/modules/env.php';
$envBootstrap($application);

return $application;
