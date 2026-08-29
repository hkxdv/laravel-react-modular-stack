<?php

declare(strict_types=1);

namespace Modules\Core\Application\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Core\Contracts\AccountSecurity\LoginAttemptInterface;

/**
 * FormRequest abstracto para autenticación genérica por guard.
 *
 * Subclases definen guard(), loginType() y redirectRoute().
 * La lógica de login permanece en la clase base.
 */
abstract class AbstractLoginRequest extends FormRequest
{
    public const string TWO_FACTOR_PENDING_SESSION_KEY = 'two_factor_login_pending';

    /**
     * Determina si el guard de autenticación.
     */
    abstract protected function guard(): string;

    /**
     * Determina el tipo de login (para logging).
     */
    abstract protected function loginType(): string;

    /**
     * Determina la ruta de redirección post-login.
     */
    abstract protected function redirectRoute(): string;

    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    final public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    final public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para el validador.
     *
     * @return array<string, string>
     */
    final public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.regex' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /**
     * Intenta autenticar las credenciales de la solicitud.
     *
     * @throws ValidationException
     */
    final public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $loginAttemptService = resolve(LoginAttemptInterface::class);
        $credentials = $this->getCredentials();
        $rawIdentifier = $this->input('email');
        $identifier = is_string($rawIdentifier)
          ? $rawIdentifier
          : $credentials['email'];
        $ipRaw = $this->ip();
        $ip = is_string($ipRaw) ? $ipRaw : '';

        $user = $this->findUser($credentials);

        if (! $user instanceof Authenticatable) {
            $this->handleFailedLogin($loginAttemptService, $identifier, $ip, 'user_not_found');
        }

        if ($user && ! $this->isUserActive($user)) {
            $this->handleFailedLogin($loginAttemptService, $identifier, $ip, 'account_inactive');
        }

        if ($user instanceof Authenticatable && $this->hasConfirmedTwoFactor($user)) {
            $this->stageTwoFactorChallenge($user);

            return;
        }

        if (
            ! Auth::guard($this->guard())->attempt($credentials, $this->boolean('remember'))
        ) {
            $this->handleFailedLogin($loginAttemptService, $identifier, $ip, 'invalid_credentials');
        }

        $loginAttemptService->clearAttempts($identifier, $ip);

        Log::info('Login exitoso', [
            'user_id' => Auth::guard($this->guard())->id(),
            'guard' => $this->guard(),
            'login_type' => $this->loginType(),
            'ip' => $ip,
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Obtiene las credenciales para el intento de autenticación.
     *
     * @return array{email: string, password: string}
     */
    final public function getCredentials(): array
    {
        $rawEmail = $this->input('email');
        $email = is_string($rawEmail) ? $rawEmail : '';
        $rawPassword = $this->input('password');
        $password = is_string($rawPassword) ? $rawPassword : '';

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * Asegura que la solicitud de inicio de sesión no esté limitada por frecuencia.
     *
     * @throws ValidationException
     */
    final public function ensureIsNotRateLimited(): void
    {
        $loginAttemptService = resolve(LoginAttemptInterface::class);
        $rawIdentifier = $this->input('email');
        $identifier = is_string($rawIdentifier) ? $rawIdentifier : '';
        $ipRaw = $this->ip();
        $ip = is_string($ipRaw) ? $ipRaw : '';

        if ($loginAttemptService->isIpBlocked($ip)) {
            event(new Lockout($this));

            Log::warning('Acceso bloqueado por IP en lista negra.', [
                'identifier' => $identifier,
                'ip' => $ip,
            ]);

            throw ValidationException::withMessages([
                'email' => __('Acceso bloqueado temporalmente por motivos de seguridad.'),
            ]);
        }

        if (! $loginAttemptService->hasTooManyAttempts($identifier, $ip)) {
            return;
        }

        event(new Lockout($this));

        $minutes = $loginAttemptService->getRemainingMinutes($identifier, $ip);
        $seconds = $minutes * 60;

        Log::warning('Bloqueo de inicio de sesión por exceso de intentos.', [
            'identifier' => $identifier,
            'ip' => $ip,
            'minutes_remaining' => $minutes,
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => $minutes,
            ]),
        ]);
    }

    /**
     * Indica si el login pendió un challenge de 2FA (usuario confirmado).
     */
    final public function needsTwoFactorChallenge(): bool
    {
        return session()->has(self::TWO_FACTOR_PENDING_SESSION_KEY);
    }

    /**
     * Obtiene la URL a la que se debe redirigir después de un inicio de sesión exitoso.
     */
    protected function getRedirectUrl(): string
    {
        $intended = session()->pull('url.intended');

        return is_string($intended)
          ? $intended
          : route($this->redirectRoute());
    }

    /**
     * Determina si el usuario ya confirmó 2FA (tiene columna two_factor_confirmed_at).
     */
    private function hasConfirmedTwoFactor(Authenticatable $user): bool
    {
        if (! $user instanceof Model) {
            return false;
        }

        $confirmedAt = $user->getAttributes()['two_factor_confirmed_at'] ?? null;

        return $confirmedAt !== null;
    }

    /**
     * Prepara el challenge: almacena el usuario pendiente en sesión y evita
     * establecer la sesión autenticada todavía.
     */
    private function stageTwoFactorChallenge(Authenticatable $user): void
    {
        session()->put(self::TWO_FACTOR_PENDING_SESSION_KEY, $user->getAuthIdentifier());

        Log::info('Login pendiente de 2FA', [
            'user_id' => $user->getAuthIdentifier(),
            'guard' => $this->guard(),
            'login_type' => $this->loginType(),
            'ip' => $this->ip(),
        ]);
    }

    /**
     * Buscar usuario por credenciales en el guard específico.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    private function findUser(array $credentials): ?Authenticatable
    {
        $provider = Config::get(sprintf('auth.guards.%s.provider', $this->guard()));
        if (! is_string($provider) || $provider === '') {
            return null;
        }

        $model = Config::get(sprintf('auth.providers.%s.model', $provider));
        if (! is_string($model) || $model === '' || ! class_exists($model)) {
            return null;
        }

        /** @var class-string<Model&Authenticatable> $modelClass */
        $modelClass = $model;
        /** @var Model|null $found */
        $found = $modelClass::query()->where('email', $credentials['email'])->first();

        return $found instanceof Authenticatable ? $found : null;
    }

    /**
     * Verificar si el usuario está activo.
     */
    private function isUserActive(Authenticatable $user): bool
    {
        if (property_exists($user, 'active') && $user->active !== null) {
            return (bool) $user->active;
        }

        if (property_exists($user, 'status') && $user->status !== null) {
            return $user->status === 'active';
        }

        return true;
    }

    /**
     * Manejar fallos de login.
     */
    private function handleFailedLogin(
        LoginAttemptInterface $loginAttemptService,
        string $identifier,
        string $ip,
        string $reason
    ): void {
        $loginAttemptService->incrementAttempts($identifier, $ip);
        $this->logFailedAttempt($reason);

        $message = match ($reason) {
            'user_not_found' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
            'account_inactive' => 'Tu cuenta está inactiva. Contacta al administrador.',
            'invalid_credentials' => 'Las credenciales proporcionadas no son correctas.',
            default => __('auth.failed')
        };

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    /**
     * Registra un intento de inicio de sesión fallido para análisis de seguridad.
     */
    private function logFailedAttempt(string $reason): void
    {
        Log::warning('Intento de inicio de sesión fallido', [
            'reason' => $reason,
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'email' => $this->input('email'),
        ]);
    }
}
