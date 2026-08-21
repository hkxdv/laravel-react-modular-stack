<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Agregador de permisos registados, agrupados por módulo.
 *
 * Reemplaza el service-locator `app()->tagged('permission-registry')` duplicado
 * en múltiples controladores, proveyendo un punto único de resolución.
 */
final class PermissionRegistryAggregator
{
    /** @var array<int, PermissionRegistryInterface> */
    private array $registries = [];

    /**
     * Registra una implementación de PermissionRegistryInterface.
     */
    public function register(PermissionRegistryInterface $registry): void
    {
        $this->registries[] = $registry;
    }

    /**
     * Devuelve los permisos agrupados por módulo.
     *
     * @return array<string, array<int, array{name: string, description: string, guard: string}>>
     */
    public function getGroupedByModule(): array
    {
        /** @var array<string, array<int, array{name: string, description: string, guard: string}>> $grouped */
        $grouped = [];

        foreach ($this->registries as $registry) {
            $grouped[$registry->moduleName()] = $registry->permissions();
        }

        return $grouped;
    }
}
