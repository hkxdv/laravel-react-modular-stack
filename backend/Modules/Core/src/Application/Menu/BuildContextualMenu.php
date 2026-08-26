<?php

declare(strict_types=1);

namespace Modules\Core\Application\Menu;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Domain\Menu\DTO\ContextualMenuItem;
use Modules\Core\Domain\Menu\DTO\PanelMenuItem;
use Modules\Core\Domain\Menu\ResolvedNavItem;
use Modules\Core\Domain\Menu\ResolvedPanelItem;
use Modules\Core\Infrastructure\Laravel\Events\MenuPermissionDenied;

/**
 * Construye navegación contextual y de panel para vistas de módulos.
 *
 * Resuelve referencias declarativas, valida DTOs de menú y registra
 * denegaciones/observabilidad. Soporta títulos dinámicos y rutas seguras.
 */
final readonly class BuildContextualMenu
{
    /**
     * @var array<string, array{
     *   textKey: string,
     *   textTemplateKey: string,
     *   extraFields: array<string, string>
     * }>
     */
    private const array NAV_TYPE_CONFIG = [
        MenuBuilderInterface::NAV_TYPE_CONTEXTUAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => [
                'href' => 'href',
                'current' => 'current',
            ],
        ],
        MenuBuilderInterface::NAV_TYPE_PANEL => [
            'textKey' => 'name',
            'textTemplateKey' => 'name_template',
            'extraFields' => [
                'route_name' => 'route_name',
                'description' => 'description',
            ],
        ],
    ];

    public function __construct(
    ) {
        //
    }

    /**
     * Ejecuta la construcción de navegación contextual o de panel.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return list<ResolvedNavItem>|list<ResolvedPanelItem>
     */
    public function execute(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        // Verificar que el tipo de navegación es válido
        if (! isset(self::NAV_TYPE_CONFIG[$navType])) {
            Log::channel('domain_navigation')->warning('Tipo de navegación desconocido: '.$navType);

            return [];
        }

        // Asegurar que la configuración sea un array secuencial de ítems
        $resolvedConfig = array_values($itemsConfig);
        /** @var array<int, array<mixed>> $resolvedConfig */
        $resolvedConfig = array_values(
            array_filter(
                $resolvedConfig,
                is_array(...)
            )
        );
        $resolvedConfig = $this->flattenResolvedConfig($resolvedConfig);
        $resolvedConfig = array_map(
            static function (array $item): array {
                $assoc = [];
                foreach ($item as $k => $v) {
                    $assoc[(string) $k] = $v;
                }

                return $assoc;
            },
            $resolvedConfig
        );

        if ($navType === MenuBuilderInterface::NAV_TYPE_CONTEXTUAL) {
            /** @var array<int, array<string, mixed>> $resolvedConfig */
            return $this->buildContextualNavItems(
                $resolvedConfig,
                $permissionChecker,
                $moduleSlug,
                $functionalName,
            );
        }

        return $this->buildPanelItems(
            $resolvedConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName,
        );
    }

    /**
     * Construye ítems de navegación contextual.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return list<ResolvedNavItem>
     */
    private function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName,
    ): array {
        $builtItems = [];

        foreach ($itemsConfig as $config) {
            $errors = ContextualMenuItem::validate($config);
            if ($errors !== []) {
                Log::channel('domain_navigation')->warning(
                    'Configuración de item inválida',
                    [
                        'module' => $moduleSlug,
                        'errors' => $errors,
                        'config' => $config,
                    ]
                );

                continue;
            }

            $permission = $config['permission'] ?? null;
            if ($permission) {
                $allowed = true;
                if (is_array($permission)) {
                    $allowed = array_any(
                        $permission,
                        fn ($perm): bool => is_string($perm) && $permissionChecker($perm)
                    );
                } elseif (is_string($permission)) {
                    $allowed = $permissionChecker($permission);
                }

                if (! $allowed) {
                    $this->recordNavPermissionDenial(
                        is_string($permission) ? $permission : null,
                        $moduleSlug
                    );

                    continue;
                }
            }

            // Determinar el texto a mostrar
            $title = isset($config['title']) && is_string($config['title'])
                ? $config['title']
                : null;
            if (
                isset($config['title_template'])
                && is_string($config['title_template'])
                && $functionalName
            ) {
                $title = sprintf($config['title_template'], $functionalName);
            }

            // Construir la ruta
            $routeName = $config['route_name'] ?? null;
            if (! $routeName) {
                $routeNameSuffix = $config['route_name_suffix'] ?? null;
                if ($routeNameSuffix && is_string($routeNameSuffix)) {
                    $routeName = sprintf('internal.staff.%s.%s', $moduleSlug, $routeNameSuffix);
                }
            }

            $href = '#';
            if ($routeName && is_string($routeName)) {
                $routeParams = $config['route_params'] ?? [];
                $normalizedParams = [];
                if (is_array($routeParams)) {
                    foreach ($routeParams as $k => $v) {
                        $normalizedParams[(string) $k] = $v;
                    }
                }

                $href = $this->generateRoute($routeName, $normalizedParams);
            } elseif (
                isset($config['href'])
                && is_string($config['href']) && $config['href'] !== ''
            ) {
                $href = $config['href'];
            } elseif (
                isset($config['route'])
                && is_string($config['route']) && $config['route'] !== ''
            ) {
                $paramsForRoute = $config['route_params'] ?? ($config['route_parameters'] ?? []);
                $normalizedParams = [];
                if (is_array($paramsForRoute)) {
                    foreach ($paramsForRoute as $k => $v) {
                        $normalizedParams[(string) $k] = $v;
                    }
                }

                $href = $this->generateRoute($config['route'], $normalizedParams);
            }

            $icon = isset($config['icon']) && is_string($config['icon'])
                ? $config['icon'] : null;

            $current = false;
            if (is_string($routeName)) {
                $current = Route::currentRouteName() === $routeName
                    || str_starts_with(Route::currentRouteName() ?? '', $routeName.'.');
            } elseif (
                isset($config['current'])
                && is_bool($config['current'])
            ) {
                $current = $config['current'];
            }

            /** @var string|array<int, string>|null $permission */
            $builtItems[] = new ResolvedNavItem(
                title: $title ?? '',
                href: $href,
                icon: $icon,
                current: $current,
                permission: $permission,
            );
        }

        /** @var list<ResolvedNavItem> $builtItems */
        return $builtItems;
    }

    /**
     * Construye ítems de panel.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return list<ResolvedPanelItem>
     */
    private function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName,
    ): array {
        $builtItems = [];

        foreach ($itemsConfig as $config) {
            $errors = PanelMenuItem::validate($config);
            if ($errors !== []) {
                Log::channel('domain_navigation')->warning(
                    'Configuración de item inválida',
                    [
                        'module' => $moduleSlug,
                        'errors' => $errors,
                        'config' => $config,
                    ]
                );

                continue;
            }

            $permission = $config['permission'] ?? null;
            if ($permission) {
                $allowed = true;
                if (is_array($permission)) {
                    $allowed = array_any(
                        $permission,
                        fn ($perm): bool => is_string($perm) && $permissionChecker($perm)
                    );
                } elseif (is_string($permission)) {
                    $allowed = $permissionChecker($permission);
                }

                if (! $allowed) {
                    $this->recordNavPermissionDenial(
                        is_string($permission) ? $permission : null,
                        $moduleSlug
                    );

                    continue;
                }
            }

            // Determinar el texto a mostrar
            $name = isset($config['name']) && is_string($config['name'])
                ? $config['name']
                : null;
            if (
                isset($config['name_template'])
                && is_string($config['name_template'])
                && $functionalName
            ) {
                $name = sprintf($config['name_template'], $functionalName);
            }

            // Construir la ruta
            $routeName = $config['route_name'] ?? null;
            if (! $routeName) {
                $routeNameSuffix = $config['route_name_suffix'] ?? null;
                if ($routeNameSuffix && is_string($routeNameSuffix)) {
                    $routeName = sprintf('internal.staff.%s.%s', $moduleSlug, $routeNameSuffix);
                }
            }

            /** @var string|null $routeNameForHref */
            $routeNameForHref = $routeName;
            if ($routeName && is_string($routeName)) {
                $routeParams = $config['route_params'] ?? [];
                $normalizedParams = [];
                if (is_array($routeParams)) {
                    foreach ($routeParams as $k => $v) {
                        $normalizedParams[(string) $k] = $v;
                    }
                }

                $routeNameForHref = $this->generateRoute($routeName, $normalizedParams);
            } elseif (
                isset($config['route'])
                && is_string($config['route']) && $config['route'] !== ''
            ) {
                $paramsForRoute = $config['route_params'] ?? ($config['route_parameters'] ?? []);
                $normalizedParams = [];
                if (is_array($paramsForRoute)) {
                    foreach ($paramsForRoute as $k => $v) {
                        $normalizedParams[(string) $k] = $v;
                    }
                }

                $routeNameForHref = $this->generateRoute($config['route'], $normalizedParams);
            }

            $icon = isset($config['icon']) && is_string($config['icon'])
                ? $config['icon'] : null;
            $description = isset($config['description']) && is_string($config['description'])
                ? $config['description'] : null;

            /** @var string|array<int, string>|null $permission */
            $builtItems[] = new ResolvedPanelItem(
                name: $name ?? '',
                icon: $icon,
                permission: $permission,
                route_name: is_string($routeName) ? ($routeNameForHref ?? null) : null,
                description: $description,
            );
        }

        /** @var list<ResolvedPanelItem> $builtItems */
        return $builtItems;
    }

    /**
     * @param  array<int, array<mixed>>  $resolvedConfig
     * @return array<int, array<mixed>>
     */
    private function flattenResolvedConfig(array $resolvedConfig): array
    {
        $flattened = [];

        foreach ($resolvedConfig as $item) {
            if (array_is_list($item)) {
                $allNestedArrays = true;
                foreach ($item as $nested) {
                    if (! is_array($nested)) {
                        $allNestedArrays = false;
                        break;
                    }
                }

                if ($allNestedArrays) {
                    foreach ($item as $nested) {
                        $flattened[] = (array) $nested;
                    }

                    continue;
                }
            }

            $flattened[] = $item;
        }

        return $flattened;
    }

    /**
     * Genera la URL de una ruta de forma segura.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function generateRoute(string $name, array $parameters = []): string
    {
        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
        } catch (Exception) {
            // Ignorar errores de ruta no encontrada
        }

        return '#';
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

        event(new MenuPermissionDenied(
            permission: $permission,
            moduleSlug: $moduleSlug,
            user: null,
            context: 'contextual_nav'
        ));
    }
}
