<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use Illuminate\Http\Request;

/**
 * Contrato del registro de presentadores de usuarios autenticados.
 *
 * Permite que módulos concretos registren sus presentadores (por tag) y que
 * Core resuelva el del guard activo sin conocer módulos específicos (OCP).
 */
interface AuthUserPresenterRegistryInterface
{
    /**
     * Registra un presentador en orden de resolución (first-match-wins).
     */
    public function register(AuthUserPresenterInterface $presenter): void;

    /**
     * Resuelve el presentador y usuario del primer guard con sesión activa.
     *
     * @param  Request  $request  Petición actual con sesión autenticada.
     * @return PresentedUser|null Presentador + usuario del guard activo, o null si no hay sesión.
     */
    public function resolve(Request $request): ?PresentedUser;
}
