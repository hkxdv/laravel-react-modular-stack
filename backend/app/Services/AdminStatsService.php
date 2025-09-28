<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\EnhancedStat;
use App\Interfaces\StatsServiceInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Illuminate\Contracts\Auth\Authenticatable;

class AdminStatsService implements StatsServiceInterface
{
    public function __construct(
        private readonly StaffUserManagerInterface $staffUserManager,
    ) {}

    /**
     * @inheritDoc
     */
    public function getPanelStats(string $moduleSlug, ?Authenticatable $user = null): array
    {
        // Totales básicos del dashboard Admin
        $totals = $this->staffUserManager->getTotals();

        return [
            new EnhancedStat(
                key: 'total_users',
                title: 'Usuarios',
                description: 'Usuarios del sistema',
                icon: 'users',
                value: (int)($totals['users'] ?? 0),
            ),
            new EnhancedStat(
                key: 'total_roles',
                title: 'Roles',
                description: 'Roles disponibles',
                icon: 'shield-check',
                value: (int)($totals['roles'] ?? 0),
            ),
        ];
    }
}
