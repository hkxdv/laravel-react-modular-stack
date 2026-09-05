<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Modules\Core\Domain\User\DTO\UserDto;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para las props de autenticación en Inertia.
 *
 * user usa UserDto|null para que el transformer de TypeScript genere el tipo
 * base común; los presentadores de módulos entregan DTOs que lo extienden.
 */
#[TypeScript]
final readonly class AuthPageProps
{
    /**
     * @param  array<string, bool>  $can
     */
    public function __construct(
        public ?UserDto $user,
        public bool $impersonate,
        public array $can = [],
    ) {
        //
    }
}
