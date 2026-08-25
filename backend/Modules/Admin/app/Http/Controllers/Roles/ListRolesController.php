<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\Roles;

use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;

/**
 * Controlador para el listado de roles del sistema.
 */
final class ListRolesController extends AbstractAdminController
{
    /**
     * Muestra la lista de roles.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con la lista de roles
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $this->authorize('viewAny', \Spatie\Permission\Models\Role::class);

        $roles = $this->rolesInterface->getAllRolesWithPermissionsCount();

        $additionalData = [
            'roles' => $roles,
            'totalRoles' => $this->rolesInterface->getTotalRoles(),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'role/list'
        );
    }
}
