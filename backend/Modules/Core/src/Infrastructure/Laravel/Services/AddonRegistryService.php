<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use App\Interfaces\AuthenticatableUser as User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Addon\AddonInstance;
use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Laravel\Module as ModuleInstance;

use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\configArray;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configString;
use function Foundry\Helpers\fileModificationTime;
use function Foundry\Helpers\userId;

/**
 * Servicio de registro y acceso a addons/módulos (implementación Laravel).
 *
 * Implementa lectura de configuraciones declarativas y estrategias
 * de caché/versionado para navegación y estado de módulos.
 */
final class AddonRegistryService implements AddonRegistryInterface
{
    /**
     * Cache de configuraciones de módulos para evitar lecturas repetidas.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configCache = [];

    public function __construct(
        private readonly ModuleConfigRegistry $moduleConfigRegistry,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableAddonsForUser(User $user): array
    {
        $this->syncModuleStatusesCache();

        // Usar collecciones para aprovechar funciones de orden superior
        return array_values(
            collect(Module::allEnabled())
                ->filter(fn ($module): bool => $module instanceof ModuleInstance
                  && $this->canUserAccessModule($user, $module))
                ->values()
                ->all()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleAddons(
        ?User $user = null
    ): array {
        $this->syncModuleStatusesCache();

        // Si no se proporciona usuario, intentar obtener el usuario autenticado desde cualquier guard
        if (! $user instanceof User) {
            $guardsArr = configArray('auth.guards');
            foreach (array_keys($guardsArr) as $guard) {
                if (Auth::guard($guard)->check()) {
                    $user = Auth::guard($guard)->user();
                    break;
                }
            }
        }

        // Si hay un usuario autenticado, filtrar por sus permisos
        if ($user) {
            return $this->getAvailableAddonsForUser($user);
        }

        // Si no hay usuario, devolver todos los módulos habilitados
        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllEnabledAddons(): array
    {
        $this->syncModuleStatusesCache();

        return array_values(
            array_filter(
                Module::allEnabled(),
                fn ($m): bool => $m instanceof ModuleInstance
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAddonConfig(string $moduleName): array
    {
        $moduleSlug = mb_strtolower($moduleName);

        // Si ya tenemos la configuración en caché, devolverla
        if (isset($this->configCache[$moduleSlug])) {
            return $this->configCache[$moduleSlug];
        }

        // Obtener la configuración y guardarla en caché
        $config = configArray($moduleSlug);

        $this->configCache[$moduleSlug] = $config;

        /** @var array<string, mixed> $config */
        return $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleConfig(string $slug): ?ModuleConfigInterface
    {
        return $this->moduleConfigRegistry->getForModule($slug);
    }

    /**
     * {@inheritDoc}
     */
    public function getAddonInstance(string $moduleName): ?AddonInstance
    {
        $config = $this->getAddonConfig($moduleName);
        if ($config === []) {
            return null;
        }

        $addonConfig = AddonConfig::fromArray($moduleName, $config);

        $guardsArr = configArray('auth.guards');
        $availableGuards = array_values(array_filter(
            array_keys($guardsArr),
            static fn (string $g): bool => $g !== ''
        ));

        if (! $addonConfig->isValidGuard($availableGuards)) {
            throw new InvalidAddonConfig(
                sprintf(
                    "El guard '%s' no está definido para el addon '%s'.",
                    (string) $addonConfig->authGuard,
                    $addonConfig->moduleSlug
                )
            );
        }

        return new AddonInstance(
            name: $moduleName,
            config: $addonConfig
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAllEnabledAddonInstances(): array
    {
        $this->syncModuleStatusesCache();

        $instances = [];

        foreach ($this->getAllEnabledAddons() as $module) {
            $name = $module->getName();
            $instance = $this->getAddonInstance($name);
            if ($instance instanceof AddonInstance) {
                $instances[] = $instance;
            }
        }

        return $instances;
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Incrementa `core.nav_version` y limpia caché interna.
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];

        $navVersionKey = configString('core.cache.nav_version_key', 'core.nav_version');
        $currentVersion = cacheInt($navVersionKey, 0);
        Cache::forever($navVersionKey, $currentVersion + 1);
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalNavItems(?User $user = null): array
    {
        $navCachePrefix = configString('core.cache.nav_cache_prefix', 'core:nav:');
        if (! str_ends_with($navCachePrefix, ':')) {
            $navCachePrefix .= ':';
        }

        $navVersionKey = configString('core.cache.nav_version_key', 'core.nav_version');
        $ttlSeconds = configInt('core.cache.global_nav_items_ttl_seconds', 300);
        if ($ttlSeconds < 1) {
            $ttlSeconds = 300;
        }

        $keyParts = ['global_nav'];
        $navVersion = cacheInt($navVersionKey, 0);
        $keyParts[] = 'nv'.$navVersion;
        if ($user instanceof User) {
            $userId = userId($user);
            $keyParts[] = $userId;
            $version = cacheInt('user.'.$userId.'.perm_version', 0);
            $keyParts[] = 'v'.$version;
            $permissions = $user instanceof AbstractDomainUser
              ? $user->getAttribute('frontend_permissions')
              : null;
            $keyParts[] = md5((string) json_encode($permissions));
        } else {
            $keyParts[] = 'guest';
        }

        $coreConfig = $this->getModuleConfig('core');
        $settingsGroup = [];
        if ($coreConfig instanceof ModuleConfigInterface) {
            $coreNav = $coreConfig->contextualNav();
            if (isset($coreNav->items['user_profile_nav'])) {
                $entries = $coreNav->items['user_profile_nav'];
                $settingsGroup = [];
                foreach ($entries as $entry) {
                    if ($entry instanceof \Modules\Core\Domain\Menu\NavComponentLink) {
                        $settingsGroup[] = [
                            'title' => $entry->title,
                            'route_name_suffix' => $entry->routeNameSuffix,
                            'icon' => $entry->icon,
                            'permission' => $entry->permission,
                        ];
                    } elseif ($entry instanceof \Modules\Core\Domain\Menu\NavComponentGroup) {
                        foreach ($entry->links as $link) {
                            $settingsGroup[] = [
                                'title' => $link->title,
                                'route_name_suffix' => $link->routeNameSuffix,
                                'icon' => $link->icon,
                                'permission' => $link->permission,
                            ];
                        }
                    }
                }
            }
        }

        $keyParts[] = md5((string) json_encode($settingsGroup));

        $cacheKey = $navCachePrefix.'global:'.md5(implode('|', $keyParts));
        $cachedRaw = cacheArray($cacheKey);
        if ($cachedRaw !== []) {
            $cachedItems = [];
            foreach ($cachedRaw as $v) {
                if (! is_array($v)) {
                    continue;
                }

                $title = is_string($v['title'] ?? null)
                  ? $v['title'] : '';
                $routeName = is_string($v['route_name'] ?? null)
                  ? $v['route_name'] : '';
                $icon = is_string($v['icon'] ?? null)
                  ? $v['icon'] : null;
                $permission = $v['permission'] ?? null;

                $cachedItems[] = [
                    'title' => $title,
                    'route_name' => $routeName,
                    'icon' => $icon,
                    'permission' => $permission,
                ];
            }

            if ($cachedItems !== []) {
                /** @var array<int, array<string, mixed>> $cachedItems */
                return $cachedItems;
            }
        }

        $items = [];
        foreach ($settingsGroup as $entry) {
            $title = $entry['title'];
            $routeName = $entry['route_name_suffix'];
            $icon = $entry['icon'];
            $permission = $entry['permission'];

            if (
                $permission !== null
                && $user instanceof User
                && ! $user->hasPermissionToCross($permission)
            ) {
                continue;
            }

            $items[] = [
                'title' => $title,
                'route_name' => $routeName,
                'icon' => $icon,
                'permission' => $permission,
            ];
        }

        Cache::put($cacheKey, $items, now()->addSeconds($ttlSeconds));

        return $items;
    }

    /**
     * Sincroniza invalidación de navegación cuando cambia el estado de módulos.
     *
     * Detecta cambios en `modules_statuses.json` vía `filemtime` y, si cambia,
     * incrementa `core.nav_version` para invalidar navegación cacheada.
     */
    private function syncModuleStatusesCache(): void
    {
        $mtimeKey = configString('core.cache.modules_statuses_mtime_key', 'core.modules_statuses_mtime');

        $statusesFile = configString(
            'modules.activators.file.statuses-file',
            base_path('modules_statuses.json')
        );

        $mtime = fileModificationTime($statusesFile);
        if ($mtime === 0) {
            return;
        }

        $cachedMtime = cacheInt($mtimeKey, -1);

        if ($cachedMtime === -1 || $cachedMtime !== $mtime) {
            $this->clearConfigCache();
            Cache::forever($mtimeKey, $mtime);
        }
    }

    /**
     * Determina si un usuario puede acceder a un módulo específico.
     */
    private function canUserAccessModule(
        User $user,
        ModuleInstance $module
    ): bool {
        $moduleConfig = $this->getModuleConfig(mb_strtolower($module->getName()));

        $canAccess = false;

        if ($moduleConfig instanceof ModuleConfigInterface) {
            $permission = $moduleConfig->addon()->basePermission;
            $permissionStr = is_string($permission) ? $permission : null;
            $authGuard = $moduleConfig->addon()->authGuard;
            $authGuardStr = is_string($authGuard) ? $authGuard : null;

            // Si el guard del módulo no coincide con el del usuario, denegar acceso.
            $guardMatches = ! $authGuardStr || $user->getAuthGuard() === $authGuardStr;

            // Preferir verificación entre guards usando método del contrato.
            $canAccess = $guardMatches
              && ($user->hasPermissionToCross('system.bypass')
                || $permissionStr === null
                || $user->hasPermissionToCross($permissionStr));
        }

        return $canAccess;
    }
}
