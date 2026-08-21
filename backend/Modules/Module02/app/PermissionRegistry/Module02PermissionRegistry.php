<?php

declare(strict_types=1);

namespace Modules\Module02\App\PermissionRegistry;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Registra los permisos granulares del módulo Module02.
 */
final class Module02PermissionRegistry implements PermissionRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        return [
            [
                'name' => 'module02.dashboard.access',
                'description' => 'Acceder al dashboard del módulo 02',
                'guard' => 'staff',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function moduleName(): string
    {
        return 'Module02';
    }
}
