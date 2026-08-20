<?php

declare(strict_types=1);

namespace Modules\Admin\App\PermissionRegistry;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Registra los permisos granulares del módulo Admin.
 */
final class AdminPermissionRegistry implements PermissionRegistryInterface
{
    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        return [
            [
                'name' => 'rbac.view',
                'description' => 'Ver panel de administración',
                'guard' => 'staff',
            ],
            [
                'name' => 'staff-users.view',
                'description' => 'Listar usuarios del personal',
                'guard' => 'staff',
            ],
            [
                'name' => 'staff-users.create',
                'description' => 'Crear usuarios del personal',
                'guard' => 'staff',
            ],
            [
                'name' => 'staff-users.update',
                'description' => 'Editar usuarios del personal',
                'guard' => 'staff',
            ],
            [
                'name' => 'staff-users.delete',
                'description' => 'Eliminar usuarios del personal',
                'guard' => 'staff',
            ],
            [
                'name' => 'staff-users.impersonate',
                'description' => 'Suplantar usuarios del personal',
                'guard' => 'staff',
            ],
            [
                'name' => 'roles.view',
                'description' => 'Ver roles del sistema',
                'guard' => 'staff',
            ],
            [
                'name' => 'roles.manage',
                'description' => 'Gestionar roles del sistema',
                'guard' => 'staff',
            ],
            [
                'name' => 'permissions.view',
                'description' => 'Ver permisos del sistema',
                'guard' => 'staff',
            ],
            [
                'name' => 'permissions.manage',
                'description' => 'Gestionar permisos del sistema',
                'guard' => 'staff',
            ],
        ];
    }
}
