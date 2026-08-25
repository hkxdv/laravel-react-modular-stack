<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Core\Contracts\StatsServiceInterface;
use Modules\Core\Domain\Stats\EnhancedStat;
use Spatie\Activitylog\Models\Activity;

/**
 * Servicio de estadísticas del dashboard para Admin.
 *
 * Expone estadísticas agregadas para el panel administrativo.
 */
final readonly class AdminStatsService implements StatsServiceInterface
{
    /**
     * @param  StaffUserManagerInterface  $staffUserManager  {@inheritDoc}
     * @param  RolesInterface  $rolesInterface  Interface para gestión de roles
     */
    public function __construct(
        private StaffUserManagerInterface $staffUserManager,
        private RolesInterface $rolesInterface,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getPanelStats(
        string $moduleSlug,
        ?Authenticatable $user = null
    ): array {
        $totalUsers = $this->staffUserManager->getTotalUsers();
        $totalRoles = $this->rolesInterface->getTotalRoles();

        return [
            new EnhancedStat(
                key: 'total_users',
                title: 'Usuarios',
                description: 'Usuarios del sistema',
                icon: 'users',
                value: $totalUsers,
            ),
            new EnhancedStat(
                key: 'total_roles',
                title: 'Roles',
                description: 'Roles disponibles',
                icon: 'shield-check',
                value: $totalRoles,
            ),
        ];
    }

    /**
     * Obtiene la actividad reciente para mostrar en el panel de administración.
     *
     * @return array<int, array{
     *     id: int,
     *     user: array{name: string},
     *     title: string,
     *     timestamp: string
     * }>
     */
    public function getRecentActivity(): array
    {
        $activities = Activity::with('causer')->latest()->take(5)->get();

        return $activities->map(function (Activity $activity): array {
            /** @var \Illuminate\Database\Eloquent\Model|null $causer */
            $causer = $activity->causer;
            $causerName = 'Sistema';
            if ($causer !== null) {
                $attr = $causer->getAttribute('name');
                $causerName = is_string($attr) ? $attr : 'Sistema';
            }

            $created = $activity->created_at;
            $timestamp = $created instanceof \Carbon\Carbon
              ? $created->toIso8601String()
              : now()->toIso8601String();

            return [
                'id' => $activity->id,
                'user' => [
                    'name' => $causerName,
                ],
                'title' => $activity->description,
                'timestamp' => $timestamp,
            ];
        })->all();
    }
}
