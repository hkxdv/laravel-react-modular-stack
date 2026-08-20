<?php

declare(strict_types=1);

namespace Modules\Examples\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Admin\App\Models\StaffUser;
use Modules\Admin\App\Services\StaffUserPresenter;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;

/**
 * Presentador compuesto para usuarios autenticados.
 *
 * Delega al presentador apropiado según el tipo de usuario:
 * - StaffUser → StaffUserPresenter (de Admin)
 * - ExampleTenantUser → presentación inline
 * - Otros → array vacío
 */
final readonly class TenantUserPresenter implements AuthUserPresenterInterface
{
    private StaffUserPresenter $staffPresenter;

    public function __construct(
        ?StaffUserPresenter $staffPresenter = null,
    ) {
        $this->staffPresenter = $staffPresenter ?? new StaffUserPresenter();
    }

    public function present(AuthenticatableUser $user): array
    {
        if ($user instanceof StaffUser) {
            return $this->staffPresenter->present($user);
        }

        return [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->getDisplayName(),
            'email' => $user->email ?? '',
            'user_type' => 'tenant',
        ];
    }
}
