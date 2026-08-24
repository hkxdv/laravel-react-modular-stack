<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request as IlluminateRequest;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Core\Domain\Stats\EnhancedStat;

/**
 * Controlador principal del panel de administración.
 *
 * Gestiona la visualización del dashboard administrativo y sus funcionalidades generales.
 */
final class AdminDashboardController extends AbstractAdminController
{
    /**
     * @param  StatsServiceInterface  $statsService  Servicio de estadísticas del panel del módulo.
     */
    public function __construct(
        private readonly StatsServiceInterface $statsService
    ) {
        //
    }

    /**
     * Renderiza el panel principal del módulo.
     *
     * @param  IlluminateRequest  $request  Request HTTP actual.
     * @return InertiaResponse Respuesta Inertia del panel.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException Si el usuario no está autenticado.
     */
    public function index(IlluminateRequest $request): InertiaResponse
    {
        $user = $this->orchestrator->resolveAuthenticatedUser(
            $request,
            $this->getModuleSlug(),
        );

        /** @var array<int, EnhancedStat> $stats */
        $stats = $this->statsService->getPanelStats(
            $this->getModuleSlug(),
            $user instanceof Authenticatable ? $user : null
        );

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: [
                'stats' => $stats,
                'recentActivity' => $this->statsService->getRecentActivity(),
            ],
            navigationService: $this->navigationBuilder,
            view: 'index'
        );
    }
}
