<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Modules\Core\Application\Auth\AbstractLoginRequest;

/**
 * FormRequest para el inicio de sesión del personal (staff).
 *
 * Hereda la lógica genérica de AbstractLoginRequest y define
 * el guard, tipo de login y ruta de redirección específicos de staff.
 */
final class LoginRequest extends AbstractLoginRequest
{
    /**
     * {@inheritDoc}
     */
    protected function guard(): string
    {
        return 'staff';
    }

    /**
     * {@inheritDoc}
     */
    protected function loginType(): string
    {
        return 'staff';
    }

    /**
     * {@inheritDoc}
     */
    protected function redirectRoute(): string
    {
        return 'internal.staff.dashboard';
    }
}
