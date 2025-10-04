<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StaffUsers;
use App\Notifications\AccountLoginNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class SecurityAuditService
{
    public function __construct(protected readonly Agent $agent) {}

    /**
     * Prepara la sesión después de una autenticación exitosa.
     * Regenera la sesión y el token para prevenir ataques de fijación de sesión.
     */
    public function prepareAuthenticatedSession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->save();
        }
    }

    /**
     * Maneja la notificación de inicio de sesión para inicios de sesión sospechosos.
     * Por el momento solo se aplica a los usuarios de tipo StaffUsers.
     */
    public function handleSuspiciousLoginNotification(Authenticatable $user, Request $request): void
    {
        if (!$user instanceof StaffUsers || !method_exists($user, 'notify')) {
            return;
        }

        try {
            $ipAddress = $request->ip() ?? 'Desconocida';
            $userAgent = $request->userAgent();

            if ($userAgent) {
                $this->agent->setUserAgent($userAgent);
            }

            $deviceInfo = [
                'device' => $this->agent->device() ?: 'Desconocido',
                'platform' => $this->agent->platform() ?: 'Desconocido',
                'browser' => $this->agent->browser() ?: 'Desconocido',
                'is_mobile' => $this->agent->isMobile(),
            ];

            // Primero, registra siempre el intento de login.
            $loginInfo = $user->recordLogin($ipAddress, $userAgent, $deviceInfo);

            $deviceDescription = trim($deviceInfo['platform'] . ' ' . $deviceInfo['browser']);
            $deviceDescription = $deviceDescription ?: 'Dispositivo desconocido';

            // Luego, si el login es sospechoso, envía la notificación.
            if ($user->isSuspiciousLogin($ipAddress, $userAgent)) {
                $user->notify(new AccountLoginNotification(
                    $ipAddress,
                    $deviceDescription,
                    'Ubicación desconocida',
                    $loginInfo->id
                ));
            }
        } catch (\Exception $e) {
            // Loguea el error con más contexto para facilitar la depuración.
            Log::warning('Error al procesar notificación de login para el usuario: ' . $user->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Cierra la sesión del usuario para un guard específico.
     */
    public function logout(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Asegurarse que la variable de sesión auth se limpia
            $request->session()->forget(['auth', 'auth.password_confirmed_at']);

            // Forzar que la sesión se guarde inmediatamente
            $request->session()->save();
        }

        // Limpiar las cookies relacionadas con la sesión
        if ($cookieName = config('session.cookie')) {
            $cookiePrefix = config('session.cookie_prefix', '');
            $request->cookies->remove($cookieName);

            // Si estamos en modo debug, loguear información
            if (config('app.debug')) {
                Log::info('Sesión cerrada para guard: ' . $guard, [
                    'cookie_name' => $cookieName,
                    'session_id' => $request->session()->getId(),
                    'cookie_removed' => true,
                ]);
            }
        }
    }
}
