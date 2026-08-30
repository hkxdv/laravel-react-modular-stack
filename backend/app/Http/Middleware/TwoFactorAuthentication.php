<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de enforcement de 2FA por guard.
 *
 * Si la política del guard (`core.guards.{guard}.two_factor_required`) exige
 * 2FA y el usuario autenticado aún no lo confirmó, se redirige a la página
 * de configuración de seguridad. Con política false (default) es no-op.
 */
final class TwoFactorAuthentication
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->currentGuard($request);

        if (! $this->guardRequiresTwoFactor($guard)) {
            return $next($request);
        }

        $user = Auth::guard($guard)->user();

        if ($user !== null && ($user->getAttributes()['two_factor_confirmed_at'] ?? null) === null) {
            return to_route('internal.staff.security.edit');
        }

        return $next($request);
    }

    private function currentGuard(Request $request): string
    {
        // La consola interna usa el guard staff; el alias se usa en ese grupo.
        $guard = $request->attributes->get('_guard', 'staff');

        return is_string($guard) ? $guard : 'staff';
    }

    private function guardRequiresTwoFactor(string $guard): bool
    {
        return (bool) config("core.guards.{$guard}.two_factor_required", false);
    }
}
