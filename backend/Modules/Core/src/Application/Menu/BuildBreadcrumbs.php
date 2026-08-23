<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\AddonRegistryInterface;

use function Foundry\Helpers\cacheArray;
use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\configInt;
use function Foundry\Helpers\configString;

/**
 * Construye breadcrumbs para rutas internas de módulos/addons.
 *
 * Soporta breadcrumbs declarativos por módulo (config) y fallback razonable.
 * Usa caché basado en `core.cache` y `core.nav_version` para invalidación global.
 */
final readonly class BuildBreadcrumbs
{
    /**
     * Constructor de la clase BuildBreadcrumbs.
     *
     * @param  AddonRegistryInterface  $moduleRegistry  Servicio de registro de módulos.
     */
    public function __construct(
        private AddonRegistryInterface $moduleRegistry,
    ) {
        //
    }

    /**
     * Construye los breadcrumbs para un módulo y un sufijo de ruta.
     *
     * Ejemplo de uso:
     * - $breadcrumbs = $builder->execute('core', 'profile.edit');
     *
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  string  $routeSuffix  Sufijo de la ruta.
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta.
     * @param  array<string, mixed>  $viewData  Datos de la vista.
     * @return array<int, array<string, mixed>> Lista de breadcrumbs.
     */
    public function execute(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $t0 = microtime(true);
        $navCachePrefix = configString('core.cache.nav_cache_prefix', 'core:nav:');
        if (! str_ends_with($navCachePrefix, ':')) {
            $navCachePrefix .= ':';
        }

        $navVersionKey = configString('core.cache.nav_version_key', 'core.nav_version');
        $ttlSeconds = configInt('core.cache.breadcrumbs_ttl_seconds', 300);
        if ($ttlSeconds < 1) {
            $ttlSeconds = 300;
        }

        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

        $navVersion = cacheInt($navVersionKey, 0);

        $key = implode('|', [
            'breadcrumbs',
            $moduleSlug,
            $routeSuffix,
            'nv'.$navVersion,
            md5((string) json_encode($routeParams)),
            md5((string) json_encode($moduleConfig)),
        ]);
        $cacheKey = $navCachePrefix.'breadcrumbs:'.md5($key);

        $cached = cacheArray($cacheKey);
        if ($cached !== []) {
            $out = [];
            foreach ($cached as $b) {
                if (! is_array($b)) {
                    continue;
                }

                $out[] = [
                    'title' => is_string($b['title'] ?? null)
                        ? $b['title']
                        : '',
                    'href' => is_string($b['href'] ?? null)
                        ? $b['href']
                        : '#',
                ];
            }

            if ($out !== []) {
                $this->logBuild($moduleSlug, $routeSuffix, count($out), true, $t0);

                return $out;
            }
        }

        // Leer breadcrumbs desde el DTO
        if (
            ! $moduleConfig instanceof \Modules\Core\Contracts\ModuleConfigInterface
            || ! isset($moduleConfig->breadcrumbs()->items[$routeSuffix])
        ) {
            $fallback = $this->getFallbackBreadcrumb($moduleConfig, $moduleSlug);
            Cache::put($cacheKey, $fallback, now()->addSeconds($ttlSeconds));

            $this->logBuild($moduleSlug, $routeSuffix, count($fallback), false, $t0);

            return $fallback;
        }

        $breadcrumbsMap = $moduleConfig->breadcrumbs();
        $breadcrumbItems = $breadcrumbsMap->items[$routeSuffix] ?? [];

        if ($breadcrumbItems === []) {
            $fallback = $this->getFallbackBreadcrumb($moduleConfig, $moduleSlug);
            Cache::put($cacheKey, $fallback, now()->addSeconds($ttlSeconds));

            $this->logBuild($moduleSlug, $routeSuffix, count($fallback), false, $t0);

            return $fallback;
        }

        $breadcrumbs = [];

        foreach ($breadcrumbItems as $crumb) {
            $title = $crumb->title;

            // Manejar títulos dinámicos
            $dynamicProp = $crumb->dynamicTitleProp;

            if ($dynamicProp !== null && $dynamicProp !== '') {
                $dynamicTitle = $this->extractDynamicTitle($dynamicProp, $viewData);
                if ($dynamicTitle !== null) {
                    $title = $title.': '.$dynamicTitle;
                }
            }

            // Determinar href
            $routeNameSuffix = $crumb->routeNameSuffix;
            $routeName = in_array($routeNameSuffix, ['', '0'], true)
                ? null
                : sprintf('internal.staff.%s.%s', $moduleSlug, $routeNameSuffix);

            $href = $routeName !== null
                ? $this->generateRoute($routeName)
                : '#';

            $breadcrumbs[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        Cache::put($cacheKey, $breadcrumbs, now()->addSeconds($ttlSeconds));

        $this->logBuild($moduleSlug, $routeSuffix, count($breadcrumbs), false, $t0);

        return $breadcrumbs;
    }

    /**
     * Construye breadcrumbs a partir de la configuración contextual (fallback).
     *
     * @param  array<int, array<string, mixed>>  $contextualItems  Ítems contextuales.
     * @param  string  $moduleSlug  Slug del módulo.
     * @param  string  $currentRoute  Ruta actual.
     * @return array<int, array<string, mixed>> Lista de breadcrumbs.
     */
    public function buildFromContextual(
        array $contextualItems,
        string $moduleSlug,
        string $currentRoute
    ): array {
        if (! str_starts_with($currentRoute, sprintf('internal.staff.%s.', $moduleSlug))) {
            $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

            return $this->getFallbackBreadcrumb(
                $moduleConfig,
                $moduleSlug
            );
        }

        // Intentar usar configuración explícita si existe
        $routeSuffix = mb_substr(
            $currentRoute,
            mb_strlen(sprintf('internal.staff.%s.', $moduleSlug))
        );
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

        if (
            $moduleConfig instanceof \Modules\Core\Contracts\ModuleConfigInterface
            && isset($moduleConfig->breadcrumbs()->items[$routeSuffix])
        ) {
            return $this->execute($moduleSlug, $routeSuffix);
        }

        // Construir desde contextual
        $breadcrumbs = [];
        if ($contextualItems !== [] && isset($contextualItems[0])) {
            $firstItem = $contextualItems[0];
            $firstTitle = isset($firstItem['title']) && is_string($firstItem['title'])
                ? $firstItem['title']
                : ucfirst($moduleSlug);
            $firstHref = isset($firstItem['href']) && is_string($firstItem['href'])
                ? $firstItem['href']
                : '#';

            $breadcrumbs[] = ['title' => $firstTitle, 'href' => $firstHref];

            foreach ($contextualItems as $item) {
                $item = (array) $item;
                $isCurrent = isset($item['current']) && $item['current'] === true;
                $itemTitle = isset($item['title']) && is_string($item['title'])
                    ? $item['title']
                    : '';

                if ($isCurrent && ($firstTitle !== $itemTitle)) {
                    $breadcrumbs[] = [
                        'title' => $itemTitle,
                        'href' => (isset($item['href']) && is_string($item['href']))
                            ? $item['href']
                            : '#',
                    ];
                    break;
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Obtiene una breadcrumb de respaldo.
     *
     * @return array<int, array<string, mixed>> Lista con la breadcrumb de respaldo.
     */
    private function getFallbackBreadcrumb(
        ?\Modules\Core\Contracts\ModuleConfigInterface $config,
        string $slug
    ): array {
        $routeName = null;
        if ($config instanceof \Modules\Core\Contracts\ModuleConfigInterface) {
            $navItem = $config->navItem();
            if ($navItem instanceof \Modules\Core\Domain\Menu\NavItem) {
                $routeName = $navItem->routeNameSuffix !== ''
                    ? $navItem->routeNameSuffix
                    : null;
            }
        }

        $routeName ??= sprintf('internal.staff.%s.index', $slug);

        return [[
            'title' => ($config instanceof \Modules\Core\Contracts\ModuleConfigInterface && $config->addon()->functionalName !== '')
                ? $config->addon()->functionalName
                : ucfirst($slug),
            'href' => $this->generateRoute($routeName),
        ]];
    }

    /**
     * Extrae un título dinámico de los datos de la vista.
     *
     * @param  string  $path  Ruta de acceso a la propiedad en los datos (ej. 'model.name').
     * @param  array<string, mixed>  $data  Datos de la vista.
     * @return string|null Título extraído o null si no se encuentra.
     */
    private function extractDynamicTitle(string $path, array $data): ?string
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Genera una URL de ruta de forma segura.
     *
     * @param  string  $routeName  Nombre de la ruta.
     * @param  array<string, mixed>  $parameters  Parámetros de la ruta.
     * @return string URL generada o '#' si falla.
     */
    private function generateRoute(string $routeName, array $parameters = []): string
    {
        try {
            if (Route::has($routeName)) {
                return route($routeName, $parameters);
            }
        } catch (Exception) {
            // Fallo silencioso intencional
        }

        return '#';
    }

    /**
     * Registra métricas sobre la construcción de breadcrumbs.
     *
     * @param  string  $slug  Slug del módulo.
     * @param  string  $suffix  Sufijo de la ruta.
     * @param  int  $count  Cantidad de breadcrumbs generados.
     * @param  bool  $hit  Indica si hubo acierto en caché.
     * @param  float  $t0  Tiempo de inicio en microsegundos.
     */
    private function logBuild(
        string $slug,
        string $suffix,
        int $count,
        bool $hit,
        float $t0
    ): void {
        $durationMs = (microtime(true) - $t0) * 1000;
        Log::channel('domain_navigation')->info('breadcrumbs_build', [
            'module_slug' => $slug,
            'route_suffix' => $suffix,
            'count' => $count,
            'cache_hit' => $hit,
            'duration_ms' => $durationMs,
        ]);
    }
}
