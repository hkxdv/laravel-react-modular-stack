<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\AuthenticatableUser;
use App\Interfaces\ModuleRegistryInterface;
use Illuminate\Support\Facades\Auth;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as ModuleInstance;

/**
 * Servicio para la gestión y acceso a módulos del sistema.
 * Proporciona métodos para obtener módulos disponibles y su configuración.
 */
class ModuleRegistryService implements ModuleRegistryInterface
{
    /**
     * Cache de configuraciones de módulos para evitar lecturas repetidas.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configCache = [];

    /**
     * Obtiene los módulos disponibles para un usuario específico.
     *
     * @return array<ModuleInstance>
     */
    public function getAvailableModulesForUser(AuthenticatableUser $user): array
    {
        // Usar collecciones para aprovechar funciones de orden superior
        return collect(Module::allEnabled())
            ->filter(fn (ModuleInstance $module) => $this->canUserAccessModule($user, $module))
            ->all();
    }

    /**
     * Obtiene los módulos accesibles basados en el usuario actual o todos si no se proporciona usuario.
     *
     * @param  AuthenticatableUser|null  $user  Usuario para el que se consultan los módulos (o null para todos)
     * @return array<ModuleInstance>
     */
    public function getAccessibleModules(?AuthenticatableUser $user = null): array
    {
        // Si no se proporciona usuario, intentar obtener el usuario autenticado desde cualquier guard
        if (!$user) {
            foreach (array_keys(config('auth.guards')) as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
        }

        // Si hay un usuario autenticado, filtrar por sus permisos
        if ($user) {
            return $this->getAvailableModulesForUser($user);
        }

        // Si no hay usuario, devolver todos los módulos habilitados
        return Module::allEnabled();
    }

    /**
     * Obtiene todos los módulos habilitados sin filtrar por usuario.
     *
     * @return array<ModuleInstance>
     */
    public function getAllEnabledModules(): array
    {
        return Module::allEnabled();
    }

    /**
     * Determina si un usuario puede acceder a un módulo específico.
     */
    private function canUserAccessModule(AuthenticatableUser $user, ModuleInstance $module): bool
    {
        $config = $this->getModuleConfig($module->getName());

        // Si no hay configuración, no permitir acceso
        if (empty($config)) {
            return false;
        }

        $permission = $config['base_permission'] ?? null;
        $authGuard = $config['auth_guard'] ?? null;

        // Si el guard del módulo no coincide con el del usuario, denegar acceso.
        if ($authGuard && $user->getAuthGuard() !== $authGuard) {
            return false;
        }

        // ADMIN y DEV (del guard 'staff') tienen acceso a todo.
        if ($user->getAuthGuard() === 'staff' && ($user->hasRole('ADMIN') || $user->hasRole('DEV'))) {
            return true;
        }

        // Si no se requiere permiso, permitir acceso.
        if ($permission === null) {
            return true;
        }

        // Verificar si el usuario tiene el permiso necesario.
        // Preferir verificación entre guards si el método está disponible.
        if (method_exists($user, 'hasPermissionToCross')) {
            return $user->hasPermissionToCross($permission);
        }

        return $authGuard ? $user->hasPermissionTo($permission, $authGuard) : $user->hasPermissionTo($permission);
    }

    /**
     * Obtiene la configuración de un módulo específico por su nombre.
     * Implementa caché para evitar lecturas repetidas de configuración.
     *
     * @return array<string, mixed>
     */
    public function getModuleConfig(string $moduleName): array
    {
        $moduleSlug = strtolower($moduleName);

        // Si ya tenemos la configuración en caché, devolverla
        if (isset($this->configCache[$moduleSlug])) {
            return $this->configCache[$moduleSlug];
        }

        // Obtener la configuración y guardarla en caché
        $config = config($moduleSlug, []);
        $this->configCache[$moduleSlug] = $config;

        return $config;
    }

    /**
     * Limpia la caché de configuraciones de módulos.
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];
    }

    /**
     * Obtiene los ítems de navegación global disponibles para un usuario.
     *
     * @param  AuthenticatableUser|null  $user  Usuario autenticado
     * @return array<int, array<string, mixed>>
     */
    public function getGlobalNavItems(?AuthenticatableUser $user = null): array
    {
        // Configuración base para los ítems de navegación global
        return [
            [
                'title' => 'Perfil',
                'route_name' => 'internal.settings.profile.edit',
                'icon' => 'UserCog',
                'permission' => null,
            ],
            [
                'title' => 'Contraseña',
                'route_name' => 'internal.settings.password.edit',
                'icon' => 'KeyRound',
                'permission' => null,
            ],
            [
                'title' => 'Apariencia',
                'route_name' => 'internal.settings.appearance',
                'icon' => 'Palette',
                'permission' => null,
            ],
        ];
    }
}
