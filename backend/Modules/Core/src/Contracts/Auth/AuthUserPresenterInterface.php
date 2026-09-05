<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use App\Interfaces\AuthenticatableUser;
use Modules\Core\Domain\User\DTO\UserDto;

/**
 * Interfaz para presentar usuarios autenticados como DTOs para vistas.
 *
 * Permite que módulos concretos (ej. Admin) definan cómo transformar
 * un usuario en datos para Inertia sin acoplar Core a modelos específicos.
 */
interface AuthUserPresenterInterface
{
    /**
     * Devuelve el guard de autenticación que este presentador resuelve.
     */
    public function guard(): string;

    /**
     * Presenta un usuario autenticado como DTO para props de Inertia.
     *
     * @param  AuthenticatableUser  $user  Usuario autenticado.
     * @return UserDto|null DTO de usuario, o null si el tipo no corresponde a este presentador.
     */
    public function present(AuthenticatableUser $user): ?UserDto;
}
