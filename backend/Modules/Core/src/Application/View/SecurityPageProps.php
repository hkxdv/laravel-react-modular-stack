<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para las props de seguridad en Inertia.
 */
#[TypeScript]
final readonly class SecurityPageProps
{
    public function __construct(
        public bool $twoFactorRequired,
        public bool $twoFactorEnabled,
        public bool $twoFactorPending,
    ) {
        //
    }
}
