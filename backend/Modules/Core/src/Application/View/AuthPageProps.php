<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Modules\Admin\App\DTO\StaffUserDto;
use Modules\Examples\App\DTO\TenantUserDto;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para las props de autenticación en Inertia.
 *
 * user y staff usan unión ?StaffUserDto|?TenantUserDto para que el transformer
 * de TypeScript genere la unión precisa de DTOs en lugar de Record<string, any>.
 */
#[TypeScript]
final readonly class AuthPageProps
{
    /**
     * @param  array<string, bool>  $can
     */
    public function __construct(
        public StaffUserDto|TenantUserDto|null $user,
        public bool $impersonate,
        public array $can = [],
    ) {
        //
    }
}
