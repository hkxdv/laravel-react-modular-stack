<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use Illuminate\Support\Env;

final class DockerEnvLoader implements EnvLoaderStrategy
{
    public function resolve(): ?string
    {
        $runningInContainerEnv = Env::get(
            'APP_RUNNING_IN_CONTAINER',
            $_SERVER['APP_RUNNING_IN_CONTAINER'] ?? null
        );

        $runningInContainer = filter_var(
            $runningInContainerEnv,
            FILTER_VALIDATE_BOOL
        ) ?: (is_file('/.env.docker'));

        if ($runningInContainer) {
            return '.envs'.DIRECTORY_SEPARATOR.'.env.docker';
        }

        return null;
    }
}
