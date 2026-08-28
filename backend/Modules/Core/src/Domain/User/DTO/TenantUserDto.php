<?php

declare(strict_types=1);

namespace Modules\Core\Domain\User\DTO;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO para usuarios tenant.
 *
 * Representa un usuario de tenant con los campos mínimos
 * necesarios para la autenticación y visualización.
 */
#[TypeScript]
final readonly class TenantUserDto implements JsonSerializable
{
    /**
     * @param  array<int, RoleDto>  $roles  Roles (vacío para tenant).
     * @param  array<int, string>  $permissions  Permisos (vacío para tenant).
     * @param  string|null  $avatar  URL del avatar (null para tenant).
     * @param  string|null  $email_verified_at  Fecha de verificación del email (null para tenant).
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $user_type,
        public array $roles = [],
        public array $permissions = [],
        public ?string $avatar = null,
        public ?string $email_verified_at = null,
    ) {
        //
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
