<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

/**
 * Contrato para registros de permisos de cada módulo.
 * Cada módulo implementa esta interfaz declarando sus permisos granulares.
 */
interface PermissionRegistryInterface
{
    /**
     * Devuelve la lista de permisos declarados por este módulo.
     *
     * @return array<int, array{name: string, description: string, guard: string}>
     */
    public function permissions(): array;

    /**
     * Devuelve el nombre del módulo al que pertenecen los permisos.
     */
    public function moduleName(): string;
}
