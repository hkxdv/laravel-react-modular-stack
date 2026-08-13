<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

interface EnvLoaderStrategy
{
    /**
     * Return env file path if this strategy matches, null to skip.
     */
    public function resolve(): ?string;
}
