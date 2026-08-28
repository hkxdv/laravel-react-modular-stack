<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use App\Interfaces\AuthenticatableUser;

/**
 * Interfaz para presentar usuarios autenticados como DTOs para vistas.
 *
 * Permite que módulos concretos (ej. Admin) definan cómo transformar
 * un usuario en datos para Inertia sin acoplar Core a modelos específicos.
 */
interface AuthUserPresenterInterface
{
    /**
     * Presenta un usuario autenticado como DTO para props de Inertia.
     *
     * @param  AuthenticatableUser  $user  Usuario autenticado.
     *
     * @phpstan-return \Modules\Core\Domain\User\DTO\StaffUserDto|\Modules\Core\Domain\User\DTO\TenantUserDto|array<never, never>
     */
    public function present(AuthenticatableUser $user): object|array;
}
