<?php

declare(strict_types=1);

namespace Modules\Examples\App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Examples\App\Models\ExampleTenantUser;

/**
 * Policy para autorización de usuarios tenant (módulo Examples).
 */
final class TenantUserPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver el listado del módulo tenant.
     */
    public function viewAny(ExampleTenantUser $user): bool
    {
        return $user->hasPermissionTo('examples.tenant.dashboard', 'tenant');
    }

    /**
     * Determina si el usuario puede crear recursos tenant.
     */
    public function create(ExampleTenantUser $user): bool
    {
        return $user->hasPermissionTo('examples.tenant.manage', 'tenant');
    }

    /**
     * Determina si el usuario puede ver un recurso tenant específico.
     */
    public function view(ExampleTenantUser $user): bool
    {
        return $user->hasPermissionTo('examples.tenant.dashboard', 'tenant');
    }

    /**
     * Determina si el usuario puede acceder al dashboard tenant.
     */
    public function dashboard(ExampleTenantUser $user): bool
    {
        return $user->hasPermissionTo('examples.tenant.dashboard', 'tenant');
    }

    /**
     * Determina si el usuario puede cerrar sesión tenant.
     */
    public function logout(ExampleTenantUser $user): bool
    {
        return $user->hasPermissionTo('examples.tenant.logout', 'tenant');
    }
}
