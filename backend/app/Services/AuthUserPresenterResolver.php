<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Modules\Admin\App\Services\StaffUserPresenter;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;
use Modules\Core\Contracts\Auth\AuthUserPresenterResolverInterface;
use Modules\Examples\App\Services\TenantUserPresenter;

/**
 * Resuelve el presenter de usuario autenticado según el guard activo.
 *
 * Detecta si el usuario autenticado proviene del guard 'staff' o 'tenant'
 * y retorna el presenter correspondiente. Esta lógica vivía en AdminServiceProvider
 * como singleton fallback — ahora se delega aquí para evitar el acoplamiento
 * permanente a StaffUserPresenter cuando el request real es de un tenant.
 *
 * Vive en el shell app/ (no en Core) porque es un puente entre módulos:
 * Core no debe depender de Admin/Examples (regla REQ-A12).
 */
final readonly class AuthUserPresenterResolver implements AuthUserPresenterResolverInterface
{
    public function __construct(
        private StaffUserPresenter $staffUserPresenter,
        private TenantUserPresenter $tenantUserPresenter,
    ) {
        //
    }

    /**
     * Resuelve el presenter según el guard del usuario autenticado.
     *
     * @param  Request  $request  Petición actual con sesión autenticada.
     * @return AuthUserPresenterInterface|null El presenter para el guard activo, o null si no hay usuario.
     */
    public function resolve(Request $request): ?AuthUserPresenterInterface
    {
        if ($request->user('staff') !== null) {
            return $this->staffUserPresenter;
        }

        if ($request->user('tenant') !== null) {
            return $this->tenantUserPresenter;
        }

        return null;
    }
}
