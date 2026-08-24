<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para las props de autenticación en Inertia.
 *
 * user y staff se mantienen como ?array para evitar acoplamiento a Eloquent.
 */
#[TypeScript]
final readonly class AuthPageProps
{
    /**
     * @param  array<string, mixed>|null  $user
     * @param  array<string, mixed>|null  $staff
     * @param  array<string, bool>  $can
     */
    public function __construct(
        public ?array $user,
        public ?array $staff,
        public bool $impersonate,
        public array $can = [],
    ) {
        //
    }
}
