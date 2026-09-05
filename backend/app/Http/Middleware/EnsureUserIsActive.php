<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para asegurar que el usuario autenticado esté activo.
 *
 * Verifica que el usuario no haya sido deshabilitado o suspendido
 * después de la autenticación inicial.
 */
final class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        ?string $guard = null
    ): Response {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            $response = $this->handleUnauthenticated($request);
        } else {
            $user = $authGuard->user();

            if ($user === null) {
                $response = $this->handleUnauthenticated($request);
            } else {
                // Verificar si el usuario está activo
                /** @phpstan-ignore instanceof.alwaysTrue (runtime guard: auth guard may return non-AbstractDomainUser) */
                $isInactive = $user instanceof AbstractDomainUser && ! $user->isActive();

                // Verificar si el usuario ha sido eliminado (soft delete)
                /** @phpstan-ignore instanceof.alwaysTrue (runtime guard: auth guard may return non-AbstractDomainUser) */
                $isDeleted = $user instanceof AbstractDomainUser && $user->trashed();

                $response = $isInactive || $isDeleted
                    ? $this->handleInactiveUser($request, $guard)
                    : $next($request);
            }
        }

        return $response;
    }

    /**
     * Maneja el caso cuando el usuario no está autenticado.
     */
    private function handleUnauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'No autenticado.',
                'error' => 'unauthenticated',
            ], 401);
        }

        return redirect()->guest(route('welcome'));
    }

    /**
     * Maneja el caso cuando el usuario está inactivo.
     */
    private function handleInactiveUser(
        Request $request,
        ?string $guard
    ): Response {
        // Cerrar la sesión del usuario inactivo
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
                'error' => 'account_inactive',
            ], 403);
        }

        // Redirigir según el guard desde config
        /** @var string $redirectRoute */
        $redirectRoute = config(sprintf('core.guards.%s.redirect_route', $guard), 'welcome');

        return to_route($redirectRoute)
            ->withErrors([
                'email' => 'Tu cuenta ha sido desactivada. Contacta al administrador para más información.',
            ]);
    }
}
