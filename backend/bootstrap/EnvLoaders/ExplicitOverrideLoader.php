<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use Illuminate\Support\Env;

final class ExplicitOverrideLoader implements EnvLoaderStrategy
{
    public function resolve(): ?string
    {
        $value = Env::get('LARAVEL_ENV_FILE', $_SERVER['LARAVEL_ENV_FILE'] ?? null);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
