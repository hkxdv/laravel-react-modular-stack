<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\Permissions;

use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Core\Infrastructure\Laravel\Services\PermissionRegistryAggregator;

/**
 * Controlador para el listado de permisos del sistema.
 */
final class ListPermissionsController extends AbstractAdminController
{
    public function __construct(
        private readonly PermissionRegistryAggregator $permissionRegistry,
    ) {
        //
    }

    /**
     * Muestra la lista de permisos agrupados por módulo.
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $this->authorize('viewAny', \Spatie\Permission\Models\Permission::class);

        $additionalData = [
            'permissionsByModule' => $this->permissionRegistry->getGroupedByModule(),
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'permission/list'
        );
    }
}
