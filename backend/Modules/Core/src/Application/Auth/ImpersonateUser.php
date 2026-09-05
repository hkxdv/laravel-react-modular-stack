<?php

declare(strict_types=1);

namespace Modules\Core\Application\Auth;

use App\Interfaces\AuthenticatableUser;
use Modules\Core\Contracts\Auth\ImpersonatesUsersInterface;

/**
 * Caso de uso: suplantación de identidad de un usuario del sistema.
 */
final readonly class ImpersonateUser
{
    public function __construct(
        private ImpersonatesUsersInterface $impersonator
    ) {
        //
    }

    /**
     * Inicia suplantación a partir del usuario destino.
     *
     * @param  AuthenticatableUser  $target  Usuario destino
     * @return bool True si la suplantación comenzó
     */
    public function handle(AuthenticatableUser $target): bool
    {
        $targetUser = $target;

        return $this->impersonator->impersonate($targetUser);
    }
}
