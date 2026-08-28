<?php

declare(strict_types=1);

namespace Modules\Core\Domain\User\DTO;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO base para usuarios autenticados.
 *
 * Representa los campos comunes a todos los tipos de usuario del sistema.
 */
#[TypeScript]
final readonly class UserDto implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $user_type,
    ) {
        //
    }

    /**
     * @return array{id: int, name: string, email: string, user_type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => $this->user_type,
        ];
    }
}
