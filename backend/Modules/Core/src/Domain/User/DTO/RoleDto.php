<?php

declare(strict_types=1);

namespace Modules\Core\Domain\User\DTO;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO para roles de usuario.
 *
 * Expone id, nombre y descripción opcional.
 */
#[TypeScript]
final readonly class RoleDto implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description = null,
    ) {
        //
    }

    /**
     * @return array{id: int, name: string, description: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
