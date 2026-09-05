<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterRegistryInterface;
use Modules\Core\Contracts\Auth\PresentedUser;

/**
 * Registro de presentadores de usuarios autenticados, agrupados por orden de registro.
 *
 * Reemplaza el resolver enumerado del shell (`AuthUserPresenterResolver`)
 * proveyendo un punto único de resolución por guard con semántica first-match-wins.
 */
final class AuthUserPresenterRegistry implements AuthUserPresenterRegistryInterface
{
    /** @var array<int, AuthUserPresenterInterface> */
    private array $presenters = [];

    /**
     * Registra un presentador de usuario autenticado.
     */
    public function register(AuthUserPresenterInterface $presenter): void
    {
        $this->presenters[] = $presenter;
    }

    /**
     * Resuelve el presentador del primer guard con usuario autenticado.
     *
     * @param  Request  $request  Petición actual con sesión autenticada.
     * @return PresentedUser|null Presentador y usuario del guard activo, o null si no hay sesión.
     */
    public function resolve(Request $request): ?PresentedUser
    {
        foreach ($this->presenters as $presenter) {
            $user = $request->user($presenter->guard());

            if ($user instanceof Authenticatable) {
                return new PresentedUser($presenter, $user);
            }
        }

        return null;
    }
}
