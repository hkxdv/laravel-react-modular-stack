<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Deprecated;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

/**
 * Controlador base de la aplicación.
 *
 * Proporciona funcionalidades comunes como la autorización de solicitudes,
 * el despacho de trabajos y la validación de datos a todos los controladores
 * que heredan de esta clase.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * Obtiene el usuario autenticado del guard especificado o aborta con 403.
     */
    protected function requireDomainUser(Request $request, string $guard = 'staff'): AbstractDomainUser
    {
        /** @var AbstractDomainUser|null $user */
        $user = $request->user($guard);

        abort_unless($user instanceof AbstractDomainUser, 403, 'Usuario no autenticado');

        return $user;
    }

    /**
     * Obtiene el usuario autenticado del guard 'staff' o aborta con 403.
     */
    #[Deprecated(message: "Use requireDomainUser(\$request, 'staff') instead.")]
    protected function requireStaffUser(Request $request): AbstractDomainUser
    {
        return $this->requireDomainUser($request, 'staff');
    }
}
