<?php

declare(strict_types=1);

namespace Modules\Examples\App\PermissionRegistry;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Registra los permisos granulares del módulo Examples.
 */
final class ExamplesPermissionRegistry implements PermissionRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        return [
            [
                'name' => 'examples.dashboard.access',
                'description' => 'Acceder al dashboard de examples (staff)',
                'guard' => 'staff',
            ],
            [
                'name' => 'examples.tenant.login',
                'description' => 'Login de tenant en examples',
                'guard' => 'tenant',
            ],
            [
                'name' => 'examples.tenant.dashboard',
                'description' => 'Acceder al dashboard de tenant en examples',
                'guard' => 'tenant',
            ],
            [
                'name' => 'examples.tenant.manage',
                'description' => 'Gestionar tenant en examples',
                'guard' => 'tenant',
            ],
            [
                'name' => 'examples.tenant.logout',
                'description' => 'Logout de tenant en examples',
                'guard' => 'tenant',
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function moduleName(): string
    {
        return 'Examples';
    }
}
