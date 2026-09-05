<?php

declare(strict_types=1);

namespace Modules\Examples\App\DTO;

use JsonSerializable;
use Modules\Core\Domain\User\DTO\RoleDto;
use Modules\Core\Domain\User\DTO\UserDto;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO para usuarios tenant.
 *
 * Extiende UserDto con los campos específicos de tenant:
 * roles, permisos, avatar y verificación de email (vacíos por defecto).
 */
#[TypeScript]
final readonly class TenantUserDto extends UserDto implements JsonSerializable
{
    /**
     * @param  string  $user_type  Literal 'tenant'.
     * @param  array<int, RoleDto>  $roles  Roles (vacío para tenant).
     * @param  array<int, string>  $permissions  Permisos (vacío para tenant).
     * @param  string|null  $avatar  URL del avatar (null para tenant).
     * @param  string|null  $email_verified_at  Fecha de verificación del email (null para tenant).
     */
    public function __construct(
        int $id,
        string $name,
        string $email,
        string $user_type,
        public array $roles = [],
        public array $permissions = [],
        public ?string $avatar = null,
        public ?string $email_verified_at = null,
    ) {
        parent::__construct($id, $name, $email, $user_type);
    }

    /**
     * @return array{id: int, name: string, email: string, user_type: string, roles: array<int, RoleDto>, permissions: array<int, string>, avatar: string|null, email_verified_at: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
