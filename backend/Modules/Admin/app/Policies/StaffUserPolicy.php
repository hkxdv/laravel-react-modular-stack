<?php

declare(strict_types=1);

namespace Modules\Admin\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admin\App\Models\StaffUser;

/**
 * Policy para autorización de usuarios del personal (Staff).
 */
final class StaffUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver la lista de staff users.
     */
    public function viewAny(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.view', 'staff');
    }

    /**
     * Determina si el usuario puede ver un staff user específico.
     */
    public function view(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.view', 'staff');
    }

    /**
     * Determina si el usuario puede crear staff users.
     */
    public function create(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.create', 'staff');
    }

    /**
     * Determina si el usuario puede actualizar staff users.
     */
    public function update(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.update', 'staff');
    }

    /**
     * Determina si el usuario puede eliminar staff users.
     */
    public function delete(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.delete', 'staff');
    }

    /**
     * Determina si el usuario puede suplantar staff users.
     */
    public function impersonate(StaffUser $user): bool
    {
        return $user->hasPermissionTo('staff-users.impersonate', 'staff');
    }
}
