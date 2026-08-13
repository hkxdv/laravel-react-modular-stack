<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use Illuminate\Support\Env;

final class TestingEnvLoader implements EnvLoaderStrategy
{
    public function resolve(): ?string
    {
        $isCli = PHP_SAPI === 'cli';
        $argv = is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : [];
        /** @var array<string> $argv */
        $argvString = implode(' ', $argv);
        $isPhpUnit = $isCli && ($argvString !== '')
            && str_contains($argvString, 'phpunit');

        $appEnv = Env::get('APP_ENV', $_SERVER['APP_ENV'] ?? null);

        if ($isPhpUnit || $appEnv === 'testing') {
            return '.envs'.DIRECTORY_SEPARATOR.'.env.testing';
        }

        return null;
    }
}
