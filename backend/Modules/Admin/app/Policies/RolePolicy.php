<?php

declare(strict_types=1);

namespace Modules\Admin\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admin\App\Models\StaffUser;

/**
 * Policy para autorización de roles del sistema.
 */
final class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de roles.
     */
    public function viewAny(StaffUser $user): bool
    {
        return $user->hasPermissionTo('roles.view', 'staff');
    }

    /**
     * Determina si el usuario puede ver un rol específico.
     */
    public function view(StaffUser $user): bool
    {
        return $user->hasPermissionTo('roles.view', 'staff');
    }

    /**
     * Determina si el usuario puede crear roles.
     */
    public function create(StaffUser $user): bool
    {
        return $user->hasPermissionTo('roles.create', 'staff');
    }

    /**
     * Determina si el usuario puede actualizar roles.
     */
    public function update(StaffUser $user): bool
    {
        return $user->hasPermissionTo('roles.update', 'staff');
    }

    /**
     * Determina si el usuario puede eliminar roles.
     */
    public function delete(StaffUser $user): bool
    {
        return $user->hasPermissionTo('roles.delete', 'staff');
    }
}
