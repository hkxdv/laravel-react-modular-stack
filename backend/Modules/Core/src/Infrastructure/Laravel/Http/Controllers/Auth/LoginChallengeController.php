<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Core\Application\Auth\AbstractLoginRequest;
use Modules\Core\Contracts\AccountSecurity\VerifyLoginChallengeInterface;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador del challenge de 2FA durante el login.
 *
 * GET renderiza la página; POST valida TOTP/recovery code contra el usuario
 * pendiente (almacenado en sesión por AbstractLoginRequest) y establece la
 * sesión autenticada del guard.
 */
final readonly class LoginChallengeController
{
    public function __construct(
        private VerifyLoginChallengeInterface $verifyLoginChallenge,
    ) {
        //
    }

    public function show(): Response
    {
        return Inertia::render('auth/two-factor-challenge');
    }

    public function verify(): Response
    {
        $user = $this->resolvePendingUser();

        if (! $user instanceof AbstractDomainUser) {
            throw ValidationException::withMessages([
                'code' => 'La sesión de 2FA ha expirado. Vuelve a iniciar sesión.',
            ]);
        }

        $validated = request()->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->verifyLoginChallenge->handle($user, $validated['code'], request()->boolean('remember'))) {
            throw ValidationException::withMessages([
                'code' => 'Código de verificación inválido.',
            ]);
        }

        session()->forget(AbstractLoginRequest::TWO_FACTOR_PENDING_SESSION_KEY);

        return Inertia::location(route('internal.staff.dashboard'));
    }

    /**
     * Resuelve el usuario pendiente desde la sesión, usando el proveedor del
     * guard por defecto (staff en la consola interna).
     */
    private function resolvePendingUser(): ?Authenticatable
    {
        $pendingId = session(AbstractLoginRequest::TWO_FACTOR_PENDING_SESSION_KEY);
        if (! is_int($pendingId) && ! is_string($pendingId) && $pendingId !== null) {
            return null;
        }

        $provider = Config::get('auth.guards.staff.provider');
        $model = is_string($provider) ? Config::get("auth.providers.{$provider}.model") : null;

        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        /** @var class-string<Model&Authenticatable> $modelClass */
        $modelClass = $model;

        $found = $modelClass::query()->find($pendingId);

        return $found instanceof Authenticatable ? $found : null;
    }
}
