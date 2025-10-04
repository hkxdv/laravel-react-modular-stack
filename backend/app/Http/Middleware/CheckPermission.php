<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\StaffUsers;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Maneja una solicitud entrante para verificar permisos de usuario.
     *
     * Este middleware centraliza la lógica de autorización para usuarios del staff
     * y responde adecuadamente a solicitudes web y API (JSON).
     *
     * @param  \Illuminate\Http\Request  $request  La solicitud HTTP entrante.
     * @param  \Closure  $next  El siguiente middleware en la cadena.
     * @param  string  $permission  El nombre del permiso a verificar.
     * @param  string|null  $guard  El guard de autenticación a utilizar. Si es nulo, se usa el por defecto.
     *
     * @throws \Spatie\Permission\Exceptions\UnauthorizedException Si el usuario no tiene permiso y la solicitud no espera JSON.
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            return $this->handleUnauthorized($request, 'notLoggedIn');
        }

        /** @var \Illuminate\Contracts\Auth\Authenticatable|\Spatie\Permission\Traits\HasRoles|\App\Traits\CrossGuardPermissions $user */
        $user = $authGuard->user();

        if ($this->userHasPermission($user, $permission, $guard)) {
            return $next($request);
        }

        return $this->handleUnauthorized($request, 'forPermissions', [$permission]);
    }

    /**
     * Comprueba si un usuario tiene un permiso determinado.
     *
     * @param  mixed  $user
     */
    protected function userHasPermission($user, string $permission, ?string $guard): bool
    {
        if ($user instanceof StaffUsers) {
            if (method_exists($user, 'hasPermissionToCross') && $user->hasPermissionToCross($permission)) {
                return true;
            }
            // Para los roles de super-admin, es crucial pasar el guard correcto.
            if (method_exists($user, 'hasRole') && $user->hasRole(['ADMIN', 'DEV'], $guard)) {
                return true;
            }
        }

        // Fallback genérico para cualquier modelo que use Spatie (si no se cumplió antes)
        if (method_exists($user, 'hasPermissionTo') && ($guard ? $user->hasPermissionTo($permission, $guard) : $user->hasPermissionTo($permission))) {
            return true;
        }

        return false;
    }

    /**
     * Maneja la respuesta para una solicitud no autorizada.
     *
     * @param  string  $exceptionType  El tipo de excepción de Spatie a lanzar ('notLoggedIn' o 'forPermissions').
     * @param  array  $exceptionArgs  Argumentos para la excepción.
     * @return \Illuminate\Http\JsonResponse|void
     */
    protected function handleUnauthorized(Request $request, string $exceptionType, array $exceptionArgs = [])
    {
        if ($request->expectsJson()) {
            $message = $exceptionType === 'notLoggedIn'
                ? 'No autenticado.'
                : 'No tienes permiso para realizar esta acción.';
            $statusCode = $exceptionType === 'notLoggedIn' ? 401 : 403;

            return response()->json(['message' => $message], $statusCode);
        }

        if ($exceptionType === 'notLoggedIn') {
            throw UnauthorizedException::notLoggedIn();
        }

        throw UnauthorizedException::forPermissions($exceptionArgs);
    }
}
