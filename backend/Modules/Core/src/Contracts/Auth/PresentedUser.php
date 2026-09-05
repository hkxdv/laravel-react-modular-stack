<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Par de presenter-usuario autenticado resuelto por el registry.
 *
 * Encapsula el resultado de una resolución de presentador: el presenter que
 * corresponde al guard activo y el usuario autenticado asociado.
 */
final readonly class PresentedUser
{
    public function __construct(
        public AuthUserPresenterInterface $presenter,
        public Authenticatable $user,
    ) {
        //
    }
}
