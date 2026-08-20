<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use App\Interfaces\AuthenticatableUser;

/**
 * Interfaz para presentar usuarios autenticados como arrays para vistas.
 *
 * Permite que módulos concretos (ej. Admin) definan cómo transformar
 * un usuario en datos para Inertia sin acoplar Core a modelos específicos.
 */
interface AuthUserPresenterInterface
{
    /**
     * Presenta un usuario autenticado como array para props de Inertia.
     *
     * @param  AuthenticatableUser  $user  Usuario autenticado.
     * @return array<string, mixed> Datos presentados del usuario, o array vacío si no soportado.
     */
    public function present(AuthenticatableUser $user): array;
}
