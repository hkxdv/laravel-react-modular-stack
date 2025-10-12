<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use App\Models\StaffUsers;
use App\Traits\PermissionVerifier;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Response as InertiaResponse;

/**
 * Controlador para el dashboard principal del sistema.
 *
 * Gestiona la visualización de la página principal a la que acceden los usuarios
 * del personal tras iniciar sesión. Se encarga de coordinar los servicios
 * para obtener los módulos disponibles y componer los datos para la vista.
 */
final class InternalDashboardController extends Controller
{
    use PermissionVerifier;

    /**
     * Constructor del controlador del dashboard.
     */
    public function __construct(
        private readonly ModuleRegistryInterface $moduleRegistryService,
        private readonly NavigationBuilderInterface $navigationBuilderService,
        private readonly ViewComposerInterface $viewComposerService,
    ) {
        $this->middleware(['auth:staff']);
    }

    /**
     * Muestra el dashboard principal con los módulos disponibles para el usuario.
     *
     * Obtiene los módulos a los que el usuario tiene acceso, prepara los datos
     * necesarios para la vista y renderiza el componente de Inertia 'internal-dashboard'.
     */
    public function index(Request $request): InertiaResponse
    {
        try {
            /** @var StaffUsers $user */
            $user = $request->user('staff');

            // Verificar que tenemos un usuario autenticado
            if (! $user) {
                Log::warning(
                    'Acceso al dashboard sin usuario autenticado',
                    [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
                abort(403, 'Usuario no autenticado');
            }

            // Verificar si el usuario está activo
            if (! $this->isUserActive($user)) {
                Auth::guard('staff')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Log::warning(
                    'Usuario inactivo intentó acceder al dashboard',
                    [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => $request->ip(),
                    ]
                );

                abort(
                    403,
                    'Tu cuenta está inactiva. Contacta al administrador.'
                );
            }

            // Actualizar última actividad
            $this->updateLastActivity($user);

            // Obtener los módulos disponibles para el usuario según sus permisos.
            $availableModules = $this->moduleRegistryService
                ->getAvailableModulesForUser($user);

            // Asegurar que la colección de módulos sea un array indexado para el frontend.
            $indexedModules = array_values($availableModules);

            // Verificar si necesita cambiar contraseña
            $passwordChangeRequired = $this->isPasswordChangeRequired($user);

            // Preparar el contexto completo para la vista
            $viewData = $this->viewComposerService
                ->composeDashboardViewContext(
                    user: $user,
                    availableModules: $indexedModules,
                    permissionChecker: fn (string $permission) => $user->hasPermissionTo($permission),
                    request: $request
                );

            // Agregar información adicional de seguridad
            $viewData = array_merge($viewData, [
                'passwordChangeRequired' => $passwordChangeRequired,
                'lastLogin' => $this->getLastLoginInfo($user),
                'sessionInfo' => $this->getSessionInfo($request),
            ]);

            // Log de acceso exitoso
            Log::info(
                'Acceso exitoso al dashboard interno',
                [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            // Renderizar la vista del dashboard
            return inertia('internal-dashboard', $viewData);
        } catch (Exception $e) {
            Log::error(
                'Error en dashboard interno',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'ip' => $request->ip(),
                ]
            );

            abort(500, 'Error interno. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Cerrar sesión de forma segura.
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::guard('staff')->user();

            if ($user) {
                Log::info(
                    'Logout exitoso del dashboard interno',
                    [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ip' => $request->ip(),
                    ]
                );
            }

            Auth::guard('staff')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')
                ->with('status', 'Sesión cerrada exitosamente.');
        } catch (Exception $e) {
            Log::error(
                'Error durante logout',
                [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                ]
            );

            return redirect()->route('login');
        }
    }

    /**
     * Verificar si el usuario está activo.
     */
    private function isUserActive(StaffUsers $user): bool
    {
        if (isset($user->active)) {
            return (bool) $user->active;
        }

        if (isset($user->status)) {
            return $user->status === 'active';
        }

        return true;
    }

    /**
     * Actualizar la última actividad del usuario.
     */
    private function updateLastActivity(StaffUsers $user): void
    {
        try {
            if ($user && method_exists($user, 'update')) {
                $user->update([
                    'last_activity' => Carbon::now(),
                ]);
            }
        } catch (Exception $e) {
            Log::warning(
                'No se pudo actualizar la última actividad',
                [
                    'user_id' => $user->id ?? null,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Verificar si se requiere cambio de contraseña.
     */
    private function isPasswordChangeRequired(StaffUsers $user): bool
    {
        if (! isset($user->password_changed_at)) {
            return false;
        }

        $maxAge = config(
            'auth.security.password_requirements.staff.max_age_days',
            90
        );
        $passwordAge = Carbon::parse(
            $user->password_changed_at
        )->diffInDays(Carbon::now());

        return $passwordAge >= $maxAge;
    }

    /**
     * Obtener información del último login.
     */
    private function getLastLoginInfo(StaffUsers $user): ?array
    {
        if (! isset($user->last_login_at)) {
            return null;
        }

        return [
            'datetime' => Carbon::parse($user->last_login_at),
            'ip' => $user->last_login_ip ?? 'Desconocida',
            'user_agent' => $user->last_login_user_agent ?? 'Desconocido',
        ];
    }

    /**
     * Obtener información de la sesión actual.
     */
    private function getSessionInfo(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => Session::getId(),
            'started_at' => Carbon::createFromTimestamp(
                Session::get('_token_created_at', time())
            ),
        ];
    }
}
