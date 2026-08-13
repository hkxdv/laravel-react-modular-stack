<?php

declare(strict_types=1);

namespace App\Bootstrap\EnvLoaders;

use RuntimeException;

final class EnvLoaderResolver
{
    /** @var array<EnvLoaderStrategy> */
    private array $strategies;

    /**
     * @param  array<EnvLoaderStrategy>  $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function resolve(): string
    {
        foreach ($this->strategies as $strategy) {
            /** @var string|null $path */
            $path = $strategy->resolve();
            if ($path !== null) {
                return $path;
            }
        }

        throw new RuntimeException('No env loader strategy matched.');
    }
}
