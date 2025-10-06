<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use App\DTO\EnhancedStat;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

/**
 * Controlador principal del Módulo 01.
 * Estandariza el contrato con el frontend y delega la lógica en el backend.
 */
class Module01PanelController extends Module01BaseController
{
    /**
     * Alias: muestra el panel del módulo 01 delegando en el controlador base.
     * Centraliza la lógica de render y reutiliza las estadísticas del módulo.
     *
     * @param  \Illuminate\Http\Request  $request  Solicitud HTTP entrante
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
