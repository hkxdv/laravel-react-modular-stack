<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use Illuminate\Support\Env;

final class ProductionEnvLoader implements EnvLoaderStrategy
{
    public function resolve(): ?string
    {
        $appEnv = Env::get('APP_ENV', $_SERVER['APP_ENV'] ?? null);

        if ($appEnv === 'production') {
            return '.envs'.DIRECTORY_SEPARATOR.'.env.production.local';
        }

        return null;
    }
}
