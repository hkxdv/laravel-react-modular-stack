<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Spatie\Activitylog\Models\Activity;

/**
 * Controlador principal del panel de administración.
 * Gestiona la visualización del dashboard administrativo y sus funcionalidades generales.
 */
class AdminPanelController extends AdminBaseController
{
    /**
     * Alias: muestra el panel del módulo de administración.
     */
    public function showModulePanel(Request $request): InertiaResponse
    {
        return parent::showModulePanel($request);
    }

    /**
     * Obtiene la actividad reciente para mostrar en el panel de administración.
     *
     * @return array<int, array{
     *     id: int,
     *     user: array{name: string},
     *     title: string,
     *     timestamp: string,
     *     icon: string
     * }>
     */
    protected function getRecentActivity(): array
    {
        $activities = Activity::with('causer')
            ->latest()
            ->take(5)
            ->get();

        return $activities->map(function (Activity $activity) {
            return [
                'id' => $activity->id,
                'user' => ['name' => $activity->causer?->name ?? 'Sistema'],
                'title' => $activity->description,
                'timestamp' => $activity->created_at->toIso8601String(),
                'icon' => $this->getIconForEvent($activity->event),
            ];
        })->toArray();
    }

    /**
     * Hook extensible: datos adicionales específicos del módulo para el panel.
     * Los controladores hijos pueden sobrescribirlo para añadir información propia.
     *
     * @return array<string, mixed> Datos adicionales a inyectar en la vista del panel
     */
    protected function getAdditionalPanelData(): array
    {
        return [
            'recentActivity' => $this->getRecentActivity(),
        ];
    }

    /**
     * Devuelve el nombre de ícono adecuado para el evento dado.
     *
     * @param  string|null  $event  Evento auditado (created, updated, deleted, etc.)
     * @return string Nombre del ícono según la semántica del evento
     */
    protected function getIconForEvent(?string $event): string
    {
        $e = strtolower((string) $event);

        return match ($e) {
            'created', 'create' => 'fileplus2',
            'updated', 'update' => 'pencil',
            'deleted', 'delete', 'removed', 'remove' => 'xcircle',
            'restored', 'restore' => 'checkcircle',
            'login', 'logged-in', 'logged_in', 'authenticated' => 'keyround',
            'logout', 'logged-out', 'logged_out' => 'lock',
            'role_assigned', 'role-granted', 'permission_assigned', 'permission-granted' => 'shieldcheck',
            'role_revoked', 'permission_revoked', 'permission-revoked' => 'shieldalert',
            default => 'activity',
        };
    }

    /**
     * Exponer las estadísticas del módulo como EnhancedStat[].
     *
     * @return array<int, \App\DTO\EnhancedStat>|null Estadísticas enriquecidas del módulo o null si no aplica
     */
    protected function getModuleStats(): ?array
    {
        // Exponer las estadísticas del módulo como EnhancedStat[]
        $stats = $this->statsService->getPanelStats($this->getModuleSlug(), $this->getAuthenticatedUser());

        return $stats;
    }
}
