<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\AddonRegistryInterface;
use Nwidart\Modules\Laravel\Module;

/**
 * Construye navegación por addons/módulos para el panel.
 *
 * Genera:
 * - ítems de barra lateral (main)
 * - ítems de sección de módulos (module)
 * - tarjetas de módulos para dashboard
 * Registra métricas y denegaciones de permisos.
 */
final readonly class BuildAddonMenu
{
    public function __construct(
        private AddonRegistryInterface $moduleRegistry
    ) {
        //
    }

    /**
     * Construye los ítems de navegación para la barra lateral.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $navItems = [];
        $totalModules = count($modules);
        $deniedCount = 0;
        $includedMain = 0;
        $includedModule = 0;

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleName);
            if (! $moduleConfig instanceof \Modules\Core\Contracts\ModuleConfigInterface) {
                continue;
            }

            if (! $this->shouldShowInNav($moduleConfig)) {
                continue;
            }

            $navItem = $moduleConfig->navItem();
            if (! $navItem instanceof \Modules\Core\Domain\Menu\NavItem) {
                continue;
            }

            $permission = $moduleConfig->addon()->basePermission;
            $allowed = ! $permission || $permissionChecker($permission);

            if ($allowed) {
                $routeName = $navItem->routeNameSuffix !== ''
                    ? $navItem->routeNameSuffix
                    : null;

                $item = [
                    'title' => $moduleConfig->addon()->functionalName !== ''
                        ? $moduleConfig->addon()->functionalName
                        : $moduleName,
                    'href' => $routeName !== null
                        ? $this->generateRoute($routeName)
                        : '#',
                    'icon' => $navItem->icon !== ''
                        ? $navItem->icon
                        : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];

                if ($navItem->showInMainNav) {
                    $navItems[] = $item;
                    $includedMain++;
                } else {
                    $includedModule++;
                }
            } else {
                $this->recordNavPermissionDenial(
                    $permission,
                    $moduleName
                );
                $deniedCount++;
            }
        }

        Log::channel('domain_navigation')->info('nav_items_build', [
            'total_modules' => $totalModules,
            'included_main' => $includedMain,
            'included_module' => $includedModule,
            'denied' => $deniedCount,
        ]);

        return $navItems;
    }

    /**
     * Construye los ítems de navegación para la lista de módulos.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        $moduleItems = [];
        $totalModules = count($modules);
        $deniedCount = 0;
        $includedCount = 0;

        foreach ($modules as $module) {
            $moduleName = mb_strtolower($module->getName());
            $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleName);
            if (! $moduleConfig instanceof \Modules\Core\Contracts\ModuleConfigInterface) {
                continue;
            }

            if (! $this->shouldShowInNav($moduleConfig)) {
                continue;
            }

            $navItem = $moduleConfig->navItem();
            if (! $navItem instanceof \Modules\Core\Domain\Menu\NavItem) {
                continue;
            }

            $permission = $moduleConfig->addon()->basePermission;
            $allowed = ! $permission || $permissionChecker($permission);
            $showInMainNav = $navItem->showInMainNav;

            if ($allowed && ! $showInMainNav) {
                $routeName = $navItem->routeNameSuffix !== ''
                    ? $navItem->routeNameSuffix
                    : null;

                $moduleItems[] = [
                    'title' => $moduleConfig->addon()->functionalName !== ''
                        ? $moduleConfig->addon()->functionalName
                        : $moduleName,
                    'href' => $routeName !== null
                        ? $this->generateRoute($routeName)
                        : '#',
                    'icon' => $navItem->icon !== ''
                        ? $navItem->icon
                        : null,
                    'current' => $routeName !== null && $this->isCurrentRoute($routeName),
                ];
                $includedCount++;
            } elseif (! $allowed) {
                $this->recordNavPermissionDenial(
                    $permission,
                    $moduleName
                );
                $deniedCount++;
            }
        }

        Log::channel('domain_navigation')->info('module_nav_build', [
            'total_modules' => $totalModules,
            'included' => $includedCount,
            'denied' => $deniedCount,
        ]);

        return $moduleItems;
    }

    /**
     * Construye las tarjetas de módulos para el dashboard.
     *
     * @param  array<Module>  $allModules
     * @param  array<Module>  $accessibleModules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        $moduleCards = [];
        $accessibleNames = [];
        foreach ($accessibleModules as $am) {
            $accessibleNames[mb_strtolower($am->getName())] = true;
        }

        foreach ($allModules as $module) {
            $moduleNameLower = mb_strtolower($module->getName());
            $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleNameLower);
            if (! $moduleConfig instanceof \Modules\Core\Contracts\ModuleConfigInterface) {
                continue;
            }

            if (! $this->shouldShowInNav($moduleConfig)) {
                continue;
            }

            $navItem = $moduleConfig->navItem();
            if (! $navItem instanceof \Modules\Core\Domain\Menu\NavItem) {
                continue;
            }

            $routeName = $navItem->routeNameSuffix !== ''
                ? $navItem->routeNameSuffix
                : null;
            $canAccess = isset($accessibleNames[$moduleNameLower]);

            $moduleCards[] = [
                'name' => $moduleConfig->addon()->functionalName !== ''
                    ? $moduleConfig->addon()->functionalName
                    : $module->getName(),
                'description' => $moduleConfig->addon()->description
                    ?? '',
                'href' => $routeName !== null
                    ? $this->generateRoute($routeName)
                    : '#',
                'icon' => $navItem->icon !== ''
                    ? $navItem->icon
                    : null,
                'canAccess' => $canAccess,
            ];
        }

        return $moduleCards;
    }

    /**
     * Determina si el módulo debe mostrarse en la navegación.
     */
    private function shouldShowInNav(
        \Modules\Core\Contracts\ModuleConfigInterface $moduleConfig
    ): bool {
        $navItem = $moduleConfig->navItem();

        if (! $navItem instanceof \Modules\Core\Domain\Menu\NavItem) {
            return false;
        }

        return $navItem->showInNav;
    }

    private function generateRoute(string $routeName): string
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName);
            }
        } catch (Exception) {
            //
        }

        return '#';
    }

    private function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();
        if (! $currentRoute) {
            return false;
        }

        return $currentRoute === $routeName
            || str_starts_with($currentRoute, $routeName.'.');
    }

    private function recordNavPermissionDenial(
        ?string $permission,
        ?string $moduleSlug = null
    ): void {
        if (! is_string($permission) || $permission === '') {
            return;
        }

        Cache::increment('metrics:navigation:denied:total');
        Cache::increment('metrics:navigation:denied:permission:'.$permission);
        if ($moduleSlug) {
            Cache::increment('metrics:navigation:denied:module:'.$moduleSlug);
        }

        Log::channel('domain_navigation')->info('permission_denied', [
            'permission' => $permission,
            'module' => $moduleSlug,
            'context' => 'module_nav',
        ]);
    }
}
