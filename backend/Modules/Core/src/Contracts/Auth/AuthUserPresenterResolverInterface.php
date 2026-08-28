<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Auth;

use Illuminate\Http\Request;

/**
 * Interfaz para resolver el presenter de usuario según el guard activo.
 *
 * Permite que la capa de presentación (ComposeInertiaProps) dependa de un
 * contrato en Core mientras el shell app/ implementa la resolución concreta
 * (puente entre módulos que Core no debe conocer).
 */
interface AuthUserPresenterResolverInterface
{
    /**
     * Resuelve el presenter adecuado para el guard del usuario autenticado.
     *
     * @param  Request  $request  Petición actual con sesión.
     * @return AuthUserPresenterInterface|null Presenter para el guard activo, o null si no hay usuario autenticado.
     */
    public function resolve(Request $request): ?AuthUserPresenterInterface;
}
