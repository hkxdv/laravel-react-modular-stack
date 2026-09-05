<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Facades\Addon;
use Modules\Core\Infrastructure\Laravel\Facades\Menu;

use function Foundry\Helpers\configString;

/**
 * Controlador base para las páginas de perfil.
 * Proporciona funcionalidades compartidas, como la construcción del menú de navegación.
 */
abstract class AbstractProfileController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Obtiene los ítems de navegación para el menú de perfil.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProfileNavigationItems(): array
    {
        return Addon::getGlobalNavItems(
            Auth::guard($this->resolveGuardFromRequest())->user()
        );
    }

    /**
     * Resuelve el guard de autenticación desde el usuario autenticado en la petición.
     *
     * La aplicación no usa el guard por defecto para sesiones reales (staff y
     * tenant son guards explícitos), por eso se consultan ambos; ante un usuario
     * no resuelto se cae al guard por defecto de la aplicación.
     */
    protected function resolveGuardFromRequest(): string
    {
        $user = request()->user('tenant') ?? request()->user('staff');

        return $user instanceof AbstractDomainUser
            ? $user->getAuthGuard()
            : configString('auth.defaults.guard');
    }

    /**
     * Obtiene el usuario autenticado del perfil o aborta con 403.
     *
     * Resuelve desde los guards activos de la aplicación (staff/tenant); las
     * rutas de perfil están protegidas por `auth:<guard>`, por lo que en la
     * práctica siempre es un AbstractDomainUser.
     */
    protected function requireProfileUser(Request $request): AbstractDomainUser
    {
        $user = $request->user('tenant') ?? $request->user('staff');

        abort_unless($user instanceof AbstractDomainUser, 403, __('Usuario no autenticado'));

        return $user;
    }

    /**
     * Construye breadcrumbs configurados para rutas de perfil.
     *
     * @param  string  $routeSuffix  Sufijo de ruta (ej. 'profile.edit', 'password.edit')
     * @return array<int, array<string, mixed>>
     */
    protected function buildBreadcrumbs(string $routeSuffix): array
    {
        return Menu::buildConfiguredBreadcrumbs('core', $routeSuffix);
    }
}
