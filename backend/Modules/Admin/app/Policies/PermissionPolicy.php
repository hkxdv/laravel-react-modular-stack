<?php

declare(strict_types=1);

namespace Modules\Admin\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admin\App\Models\StaffUser;

/**
 * Policy para autorización de permisos del sistema.
 */
final class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de permisos.
     */
    public function viewAny(StaffUser $user): bool
    {
        return $user->hasPermissionTo('permissions.view', 'staff');
    }

    /**
     * Determina si el usuario puede ver un permiso específico.
     */
    public function view(StaffUser $user): bool
    {
        return $user->hasPermissionTo('permissions.view', 'staff');
    }

    /**
     * Determina si el usuario puede actualizar permisos.
     */
    public function update(StaffUser $user): bool
    {
        return $user->hasPermissionTo('permissions.manage', 'staff');
    }
}
