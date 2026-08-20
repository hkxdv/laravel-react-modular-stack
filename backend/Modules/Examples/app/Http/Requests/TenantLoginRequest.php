<?php

declare(strict_types=1);

namespace Modules\Examples\App\Http\Requests;

use Modules\Core\Application\Auth\AbstractLoginRequest;

/**
 * FormRequest para el inicio de sesión de usuarios tenant.
 *
 * Hereda la lógica genérica de AbstractLoginRequest y define
 * el guard, tipo de login y ruta de redirección específicos de tenant.
 */
final class TenantLoginRequest extends AbstractLoginRequest
{
    /**
     * {@inheritDoc}
     */
    protected function guard(): string
    {
        return 'tenant';
    }

    /**
     * {@inheritDoc}
     */
    protected function loginType(): string
    {
        return 'tenant';
    }

    /**
     * {@inheritDoc}
     */
    protected function redirectRoute(): string
    {
        return 'internal.tenant.examples.index';
    }
}
