<?php

declare(strict_types=1);

namespace Modules\Examples\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;

/**
 * Presentador para usuarios tenant.
 *
 * Serializa un ExampleTenantUser al formato esperado por el frontend Inertia.
 */
final readonly class TenantUserPresenter implements AuthUserPresenterInterface
{
    public function present(AuthenticatableUser $user): array
    {
        return [
            'id' => $user->getAuthIdentifier(),
            'name' => $user->getDisplayName(),
            'email' => $user->email ?? '',
            'user_type' => 'tenant',
        ];
    }
}
