<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\Roles;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Http\Requests\RoleCreateRequest;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Core\Infrastructure\Laravel\Services\PermissionRegistryAggregator;
use Spatie\Permission\Models\Role;

/**
 * Controlador para la creación de roles del sistema.
 */
final class CreateRoleController extends AbstractAdminController
{
    public function __construct(
        private readonly RolesInterface $rolesInterface,
        private readonly PermissionRegistryAggregator $permissionRegistry,
    ) {
        //
    }

    /**
     * Muestra el formulario de creación de un nuevo rol.
     */
    public function create(IlluminateRequest $request): InertiaResponse
    {
        $this->authorize('create', Role::class);

        $additionalData = [
            'permissionsByModule' => $this->permissionRegistry->getGroupedByModule(),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'role/create'
        );
    }

    /**
     * Almacena un nuevo rol.
     */
    public function store(RoleCreateRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        /** @var array{name: string, permissions?: list<string>} $validated */
        $validated = $request->validated();

        $this->rolesInterface->createRole([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return to_route('internal.staff.admin.roles.index')
            ->with(
                'success',
                sprintf("Rol '%s' creado exitosamente.", $validated['name'])
            );
    }
}
