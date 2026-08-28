<?php

declare(strict_types=1);

namespace Modules\Examples\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Domain\User\DTO\TenantUserDto;

/**
 * Presentador para usuarios tenant.
 *
 * Construye TenantUserDto directamente desde el modelo ExampleTenantUser.
 */
final readonly class TenantUserPresenter implements AuthUserPresenterInterface
{
    /**
     * @phpstan-return TenantUserDto|array<never, never>
     */
    public function present(AuthenticatableUser $user): object|array
    {
        if (! $user instanceof \Modules\Examples\App\Models\ExampleTenantUser) {
            return [];
        }

        /** @var int $identifier */
        $identifier = $user->getAuthIdentifier();
        $displayName = $user->getDisplayName();
        /** @var string $email */
        $email = $user->email ?? '';

        return new TenantUserDto(
            id: $identifier,
            name: $displayName,
            email: $email,
            user_type: 'tenant',
        );
    }
}
