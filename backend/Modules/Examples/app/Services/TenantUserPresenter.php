<?php

declare(strict_types=1);

namespace Modules\Examples\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Domain\User\DTO\UserDto;
use Modules\Examples\App\DTO\TenantUserDto;
use Modules\Examples\App\Models\ExampleTenantUser;

/**
 * Presentador para usuarios tenant.
 *
 * Construye TenantUserDto directamente desde el modelo ExampleTenantUser.
 */
final readonly class TenantUserPresenter implements AuthUserPresenterInterface
{
    /**
     * Devuelve el guard de autenticación que este presentador resuelve.
     */
    public function guard(): string
    {
        return 'tenant';
    }

    /**
     * Presenta un usuario tenant como DTO de Inertia.
     *
     * @param  AuthenticatableUser  $user  Usuario autenticado.
     * @return UserDto|null DTO del tenant, o null si el usuario no es ExampleTenantUser.
     */
    public function present(AuthenticatableUser $user): ?UserDto
    {
        if (! $user instanceof ExampleTenantUser) {
            return null;
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
