<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use Illuminate\Support\Env;

final class LocalEnvLoader implements EnvLoaderStrategy
{
    public function resolve(): string
    {
        // Ambiguous config guard: if APP_KEY is set OR APP_ENV is set to a non-production
        // value without LARAVEL_ENV_FILE, we have a misconfigured env and must fail.
        if (
            Env::get('APP_KEY') !== null
            || (Env::get('APP_ENV') !== null && Env::get('APP_ENV') !== 'production')
        ) {
            $msg = "\n[FATAL] Configuración de entorno ambigua detectada.\n"
                ."Se encontraron variables de entorno inyectadas pero falta 'LARAVEL_ENV_FILE'.\n";
            defined('STDERR') ? fwrite(STDERR, $msg) : error_log($msg);
            exit(1);
        }

        return '.envs'.DIRECTORY_SEPARATOR.'.env.local';
    }
}
