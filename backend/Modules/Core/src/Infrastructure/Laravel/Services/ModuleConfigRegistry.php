<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\ModuleConfigInterface;

/**
 * Agregador de configuraciones de módulos, claveado por slug.
 */
final class ModuleConfigRegistry
{
    /** @var array<string, ModuleConfigInterface> */
    private array $configs = [];

    /**
     * Registra una implementación de ModuleConfigInterface.
     */
    public function register(ModuleConfigInterface $config): void
    {
        $addon = $config->addon();
        $this->configs[$addon->moduleSlug] = $config;
    }

    /**
     * Devuelve todas las configuraciones registradas.
     *
     * @return array<string, ModuleConfigInterface>
     */
    public function getAll(): array
    {
        return $this->configs;
    }

    /**
     * Devuelve la configuración para un módulo específico.
     */
    public function getForModule(string $slug): ?ModuleConfigInterface
    {
        return $this->configs[$slug] ?? null;
    }
}
