<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Domain\Filters\StaffUserFilter;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Http\Resources\StaffUserResource;
use Modules\Admin\App\Models\StaffUser;

/**
 * Controlador para la gestión de listado de usuarios del personal administrativo.
 */
final class ListStaffUsersController extends AbstractAdminController
{
    /**
     * Muestra la lista de usuarios.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con la lista de StaffUser paginada
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $this->authorize('viewAny', StaffUser::class);

        $filter = StaffUserFilter::fromRequest($request);

        $additionalData = [
            'users' => StaffUserResource::collection($this->staffUserManager->getAllUsers($filter)),
            'roles' => $this->rolesInterface->getAllRoles(),
            'filters' => $request->only([
                'search',
                'role',
                'sort_field',
                'sort_direction',
            ]),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'user/list'
        );
    }
}
