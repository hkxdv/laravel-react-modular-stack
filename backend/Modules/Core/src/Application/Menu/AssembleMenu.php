<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use App\Interfaces\AuthenticatableUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Domain\Menu\ResolvedNavItem;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configString;
use function Foundry\Helpers\fileModificationTime;
use function Foundry\Helpers\userId;

/**
 * Ensambla la estructura completa de navegación del panel interno.
 *
 * Centraliza la construcción de:
 * - navegación principal de addons
 * - navegación por módulo
 * - navegación contextual
 * - navegación global
 * - breadcrumbs
 *
 * Incluye caché basado en usuario, versiones (permisos y navegación) y contexto de ruta.
 */
final readonly class AssembleMenu
{
    /**
     * @param  AddonRegistryInterface  $moduleRegistry  Registro y configuración de addons.
     * @param  BuildAddonMenu  $buildModule  Builder de navegación de addons.
     * @param  BuildGlobalMenu  $buildGlobal  Builder de navegación global.
     * @param  BuildContextualMenu  $buildContextual  Builder de navegación contextual.
     * @param  BuildBreadcrumbs  $buildBreadcrumbs  Builder de breadcrumbs.
     */
    public function __construct(
        private AddonRegistryInterface $moduleRegistry,
        private BuildAddonMenu $buildModule,
        private BuildGlobalMenu $buildGlobal,
        private BuildContextualMenu $buildContextual,
        private BuildBreadcrumbs $buildBreadcrumbs
    ) {
        //
    }

    /**
     * Ensambla la estructura de navegación para una vista/ruta.
     *
     * Usa la configuración `core.cache` para prefijos, keys y TTL.
     *
     * Ejemplo de uso (en un composer/controller):
     * - $nav = $assembler->execute(fn (string $p) => $user->hasPermissionToCross($p), moduleSlug: 'admin');
     *
     * @param  callable  $permissionChecker  Función para verificar permisos.
     * @param  string|null  $moduleSlug  Slug del módulo actual.
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig  Configuración de ítems contextuales.
     * @param  AuthenticatableUser|null  $user  Usuario autenticado.
     * @param  string|null  $functionalName  Nombre funcional del módulo.
     * @param  string|null  $routeSuffix  Sufijo de la ruta actual.
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta.
     * @param  array<string, mixed>  $viewData  Datos de la vista.
     * @return array<string, mixed> Estructura completa de navegación.
     */
    public function execute(
        callable $permissionChecker,
        ?string $moduleSlug = null,
        array $contextualItemsConfig = [],
        ?AuthenticatableUser $user = null,
        ?string $functionalName = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $t0 = microtime(true);
        $req = request();
        $cacheMap = (array) $req->attributes->get('navigation_cache', []);

        $navCachePrefix = configString('core.cache.nav_cache_prefix', 'core:nav:');
        if (! str_ends_with($navCachePrefix, ':')) {
            $navCachePrefix .= ':';
        }

        $navVersionKey = configString('core.cache.nav_version_key', 'core.nav_version');
        $navTtlSeconds = configInt('core.cache.nav_assembled_ttl_seconds', 60);
        if ($navTtlSeconds < 1) {
            $navTtlSeconds = 60;
        }

        $uid = 'guest';
        $staffUser = null;
        $permVersion = 0;
        if ($user instanceof AbstractDomainUser) {
            $staffUser = $user;
            $uid = userId($user);
            $permVersion = $uid !== 'anonymous'
                ? cacheInt('user.'.$uid.'.perm_version', 0)
                : 0;
        }

        $navVersion = cacheInt($navVersionKey, 0);
        $statusesFile = configString(
            'modules.activators.file.statuses-file',
            base_path('modules_statuses.json')
        );
        $modulesMtime = fileModificationTime($statusesFile);

        // Clave de caché
        $key = implode('|', [
            $moduleSlug ?? '',
            $routeSuffix ?? '',
            $uid,
            'pv'.$permVersion,
            'nv'.$navVersion,
            'mm'.$modulesMtime,
            md5((string) json_encode($contextualItemsConfig)),
            md5((string) json_encode($routeParams)),
            md5((string) json_encode($viewData)),
        ]);

        if (isset($cacheMap[$key]) && is_array($cacheMap[$key])) {
            /** @var array<string, mixed> $cachedResult */
            $cachedResult = $cacheMap[$key];
            $this->logAssembly($moduleSlug, $routeSuffix, $uid, $cachedResult, true, $t0);

            return $cachedResult;
        }

        $cacheKey = $navCachePrefix.'assembled:'.md5($key);
        $cachedStore = cacheArray($cacheKey);
        if ($cachedStore !== []) {
            $cacheMap[$key] = $cachedStore;
            $req->attributes->set('navigation_cache', $cacheMap);
            $this->logAssembly($moduleSlug, $routeSuffix, $uid, $cachedStore, true, $t0);

            return $cachedStore;
        }

        $result = $this->buildNavigation(
            $permissionChecker,
            $moduleSlug,
            $contextualItemsConfig,
            $staffUser,
            $functionalName,
            $routeSuffix,
            $routeParams,
            $viewData
        );

        Cache::put($cacheKey, $result, now()->addSeconds($navTtlSeconds));

        $cacheMap[$key] = $result;
        $req->attributes->set('navigation_cache', $cacheMap);

        $this->logAssembly($moduleSlug, $routeSuffix, $uid, $result, false, $t0);

        return $result;
    }

    /**
     * Construye navegación y breadcrumbs a partir de config y permisos.
     *
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig
     * @param  array<string, mixed>  $routeParams
     * @param  array<string, mixed>  $viewData
     * @return array<string, mixed>
     */
    private function buildNavigation(
        callable $permissionChecker,
        ?string $moduleSlug,
        array $contextualItemsConfig,
        ?AuthenticatableUser $staffUser,
        ?string $functionalName,
        ?string $routeSuffix,
        array $routeParams,
        array $viewData
    ): array {
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($staffUser);
        $globalNavItems = $this->buildGlobal->execute($globalItemsConfig, $permissionChecker);

        $modules = $this->moduleRegistry->getAccessibleAddons($staffUser);

        $mainNavItems = $this->buildModule->buildNavItems(
            $modules,
            $permissionChecker
        );

        $moduleNavItems = $this->buildModule->buildModuleNavItems(
            $modules,
            $permissionChecker
        );

        $contextualNavItems = [];
        if ($contextualItemsConfig !== []) {
            /** @var list<ResolvedNavItem> $contextualNavItems */
            $contextualNavItems = $this->buildContextual->execute(
                MenuBuilderInterface::NAV_TYPE_CONTEXTUAL,
                $contextualItemsConfig,
                $permissionChecker,
                $moduleSlug ?? 'core',
                $functionalName
            );
        }

        $breadcrumbs = [];
        if ($moduleSlug && $routeSuffix) {
            $breadcrumbs = $this->buildBreadcrumbs->execute(
                $moduleSlug,
                $routeSuffix,
                $routeParams,
                $viewData
            );
        } elseif ($moduleSlug && $contextualNavItems !== []) {
            $breadcrumbs = $this->buildBreadcrumbs->buildFromContextual(
                $contextualNavItems,
                $moduleSlug,
                \Illuminate\Support\Facades\Route::currentRouteName() ?? ''
            );
        }

        return [
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => $contextualNavItems,
            'globalNavItems' => $globalNavItems,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    /**
     * Registra métricas del ensamblaje para observabilidad.
     *
     * @param  string|null  $slug  Slug del módulo.
     * @param  string|null  $suffix  Sufijo de la ruta.
     * @param  string  $uid  Identificador del usuario.
     * @param  array<string, mixed>  $data  Datos generados.
     * @param  bool  $hit  Indica si hubo acierto en caché.
     * @param  float  $t0  Tiempo de inicio en microsegundos.
     */
    private function logAssembly(
        ?string $slug,
        ?string $suffix,
        string $uid,
        array $data,
        bool $hit,
        float $t0
    ): void {
        $durationMs = (microtime(true) - $t0) * 1000;

        /** @var array<mixed> $main */
        $main = $data['mainNavItems'] ?? [];
        /** @var array<mixed> $mod */
        $mod = $data['moduleNavItems'] ?? [];
        /** @var array<mixed> $ctx */
        $ctx = $data['contextualNavItems'] ?? [];
        /** @var array<mixed> $glob */
        $glob = $data['globalNavItems'] ?? [];
        /** @var array<mixed> $crumbs */
        $crumbs = $data['breadcrumbs'] ?? [];

        Log::channel('domain_navigation')->info('navigation_assemble', [
            'module_slug' => $slug,
            'route_suffix' => $suffix,
            'user_id' => $uid,
            'counts' => [
                'main' => count($main),
                'module' => count($mod),
                'contextual' => count($ctx),
                'global' => count($glob),
                'breadcrumbs' => count($crumbs),
            ],
            'cache_hit' => $hit,
            'duration_ms' => $durationMs,
        ]);
    }
}
