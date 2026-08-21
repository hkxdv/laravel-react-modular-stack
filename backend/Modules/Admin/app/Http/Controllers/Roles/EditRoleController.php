<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\Roles;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Http\Requests\RoleUpdateRequest;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Core\Infrastructure\Laravel\Services\PermissionRegistryAggregator;
use Spatie\Permission\Models\Role;

/**
 * Controlador para la edición de roles del sistema.
 */
final class EditRoleController extends AbstractAdminController
{
    public function __construct(
        private readonly RolesInterface $rolesInterface,
        private readonly PermissionRegistryAggregator $permissionRegistry,
    ) {
        //
    }

    /**
     * Muestra el formulario de edición de un rol existente.
     */
    public function edit(IlluminateRequest $request, Role $role): InertiaResponse
    {
        $this->authorize('update', $role);

        $permissions = $this->rolesInterface->getRolePermissions((int) $role->id);

        $additionalData = [
            'role' => $role,
            'rolePermissions' => $permissions->pluck('name')->values()->all(),
            'permissionsByModule' => $this->permissionRegistry->getGroupedByModule(),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'role/edit'
        );
    }

    /**
     * Actualiza un rol existente.
     */
    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        /** @var array{name: string, permissions?: list<string>} $validated */
        $validated = $request->validated();

        $this->rolesInterface->updateRole((int) $role->id, [
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return to_route('internal.staff.admin.roles.index')
            ->with(
                'success',
                sprintf("Rol '%s' actualizado exitosamente.", $validated['name'])
            );
    }
}
