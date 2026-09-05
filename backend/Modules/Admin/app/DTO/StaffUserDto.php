<?php

declare(strict_types=1);

namespace Modules\Admin\App\DTO;

use JsonSerializable;
use Modules\Core\Domain\User\DTO\RoleDto;
use Modules\Core\Domain\User\DTO\UserDto;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO para usuarios del staff (personal interno).
 *
 * Extiende UserDto con campos específicos del staff:
 * verificación de email, roles, permisos y avatar.
 */
#[TypeScript]
final readonly class StaffUserDto extends UserDto implements JsonSerializable
{
    /**
     * @param  string  $user_type  Literal 'staff'.
     * @param  array<int, RoleDto>  $roles  Roles asignados al usuario.
     * @param  array<int, string>  $permissions  Permisos efectivos del usuario.
     * @param  string|null  $email_verified_at  Fecha ISO8601 de verificación del email.
     * @param  string|null  $avatar  URL del avatar (generado desde el nombre).
     */
    public function __construct(
        int $id,
        string $name,
        string $email,
        string $user_type,
        public array $roles = [],
        public array $permissions = [],
        public ?string $email_verified_at = null,
        public ?string $avatar = null,
    ) {
        parent::__construct($id, $name, $email, $user_type);
    }

    /**
     * @return array{id: int, name: string, email: string, email_verified_at: string|null, user_type: string, roles: array<int, RoleDto>, permissions: array<int, string>, avatar: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'user_type' => $this->user_type,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
            'avatar' => $this->avatar,
        ];
    }
}
