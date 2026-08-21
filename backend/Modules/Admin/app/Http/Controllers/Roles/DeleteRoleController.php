<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\Roles;

use Illuminate\Http\RedirectResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Interfaces\RolesInterface;
use Spatie\Permission\Models\Role;

/**
 * Controlador para la eliminación de roles del sistema.
 */
final class DeleteRoleController extends AbstractAdminController
{
    /**
     * Inyecta las dependencias necesarias para la gestión de roles.
     */
    public function __construct(
        private readonly RolesInterface $rolesInterface,
    ) {
        //
    }

    /**
     * Elimina un rol existente.
     *
     * @param  Role  $role  Rol a eliminar
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $deleted = $this->rolesInterface->deleteRole((int) $role->id);

        if ($deleted) {
            return to_route('internal.staff.admin.roles.index')
                ->with(
                    'success',
                    sprintf("Rol '%s' eliminado exitosamente.", $role->name)
                );
        }

        return to_route('internal.staff.admin.roles.index')
            ->with('error', 'No se puede eliminar un rol protegido.');
    }
}
