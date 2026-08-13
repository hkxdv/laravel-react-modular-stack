<?php

declare(strict_types=1);

use App\Bootstrap\EnvLoaders\DockerEnvLoader;
use App\Bootstrap\EnvLoaders\EnvLoaderResolver;
use App\Bootstrap\EnvLoaders\ExplicitOverrideLoader;
use App\Bootstrap\EnvLoaders\LocalEnvLoader;
use App\Bootstrap\EnvLoaders\ProductionEnvLoader;
use App\Bootstrap\EnvLoaders\TestingEnvLoader;
use Dotenv\Dotenv;
use Illuminate\Foundation\Application;
use Illuminate\Support\Env;

return function (Application $application): void {
    $resolver = new EnvLoaderResolver([
        new ExplicitOverrideLoader(),
        new TestingEnvLoader(),
        new DockerEnvLoader(),
        new ProductionEnvLoader(),
        new LocalEnvLoader(),
    ]);

    $envFileName = $resolver->resolve();
    $projectRoot = realpath(dirname($application->basePath())) ?: dirname($application->basePath());

    // Absolute vs relative path
    if (
        str_starts_with($envFileName, '/')
        || preg_match('/^[a-zA-Z]:\\\\/', $envFileName)
    ) {
        $envPath = $envFileName;
        $projectRoot = dirname($envPath);
        $envFileName = basename($envPath);
    } else {
        $envPath = $projectRoot.DIRECTORY_SEPARATOR.$envFileName;
    }

    if (! is_file($envPath)) {
        $msg = "\n[FATAL] Archivo de entorno no encontrado: {$envPath}\n";
        defined('STDERR') ? fwrite(STDERR, $msg) : error_log($msg);
        exit(1);
    }

    Dotenv::createImmutable($projectRoot, $envFileName)->safeLoad();

    foreach (['APP_ENV', 'APP_KEY'] as $key) {
        if (Env::get($key) === null || Env::get($key) === '') {
            $msg = "\n[FATAL] Variable de entorno requerida ausente: {$key}\n";
            defined('STDERR') ? fwrite(STDERR, $msg) : error_log($msg);
            exit(1);
        }
    }
};
