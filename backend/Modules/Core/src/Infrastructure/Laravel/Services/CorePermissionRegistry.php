<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Registra los permisos granulares del módulo Core.
 */
final class CorePermissionRegistry implements PermissionRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        return [
            [
                'name' => 'system.bypass',
                'description' => 'Acceso total al sistema (ADMIN/DEV)',
                'guard' => 'staff',
            ],
            [
                'name' => 'permissions.sync',
                'description' => 'Sincronizar permisos del sistema',
                'guard' => 'staff',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function moduleName(): string
    {
        return 'Core';
    }
}
