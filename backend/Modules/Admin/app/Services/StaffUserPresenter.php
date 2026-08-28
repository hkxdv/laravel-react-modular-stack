<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Domain\User\DTO\RoleDto;
use Modules\Core\Domain\User\DTO\StaffUserDto;
use Spatie\Permission\Models\Role;

/**
 * Presenta usuarios StaffUser como DTOs para props de Inertia.
 *
 * Construye StaffUserDto directamente desde el modelo StaffUser,
 * sin depender de StaffUserResource (capa HTTP) para mantener
 * el límite entre Domain y Application.
 */
final class StaffUserPresenter implements AuthUserPresenterInterface
{
    /**
     * @phpstan-return StaffUserDto|array<never, never>
     */
    public function present(AuthenticatableUser $user): object|array
    {
        if (! $user instanceof StaffUser) {
            return [];
        }

        $user->loadMissing('roles');

        /** @var array<int, RoleDto> $roleDtos */
        $roleDtos = $user->roles->map(function ($role): RoleDto {
            /** @var Role $role */
            /** @var int $id */
            $id = $role->id;
            /** @var string $name */
            $name = $role->name;

            return new RoleDto(id: $id, name: $name);
        })->all();

        /** @var array<int, string> $permissions */
        $permissions = $user->getAllPermissions()->pluck('name')->all();

        /** @var int $identifier */
        $identifier = $user->getAuthIdentifier();
        $displayName = $user->getDisplayName();
        /** @var string $email */
        $email = $user->email;
        /** @var string|null */
        $avatar = $user->avatar;

        return new StaffUserDto(
            id: $identifier,
            name: $displayName,
            email: $email,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            user_type: 'staff',
            roles: $roleDtos,
            permissions: $permissions,
            avatar: $avatar,
        );
    }
}
