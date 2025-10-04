<?php

declare(strict_types=1);

namespace Modules\Module02\App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use App\DTO\EnhancedStat;

class Module02PanelController extends Module02BaseController
{

    /**
     * Alias: muestra el panel del módulo 02 delegando en el controlador base.
     * Centraliza la lógica de render y reutiliza las estadísticas del módulo.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP entrante
     * @return \Inertia\Response Respuesta Inertia con la vista del panel
     */
    public function showModulePanel(Request $request): InertiaResponse
    {
        return parent::showModulePanel($request);
    }

    /**
     * Implementación concreta para obtener estadísticas del módulo.
     * Devuelve un array de EnhancedStat consumible por el frontend.
     *
     * @return array<int, \App\DTO\EnhancedStat>|null
     */
    protected function getModuleStats(): ?array
    {
        $user = $this->getAuthenticatedUser();
        $stats = $this->statsService->getPanelStats($this->getModuleSlug(), $user);

        return is_array($stats) ? $stats : [];
    }
}
