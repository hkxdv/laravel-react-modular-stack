<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Laravel\Module;
use App\DTO\PanelItem;
use App\DTO\ContextualNavItem;

/**
 * Servicio para la construcción de elementos de navegación del sistema.
 */
class NavigationBuilderService implements NavigationBuilderInterface
{
    /**
     * Mapeo de tipos de navegación a sus configuraciones específicas.
     *
     * @var array<string, array<string, string|array>>
     */
    private const NAV_TYPE_CONFIG = [
        self::NAV_TYPE_CONTEXTUAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => ['href' => 'route', 'current' => 'current'],
        ],
        self::NAV_TYPE_PANEL => [
            'textKey' => 'name',
            'textTemplateKey' => 'name_template',
            'extraFields' => ['route_name' => 'route_name', 'description' => 'description'],
        ],
        self::NAV_TYPE_GLOBAL => [
            'textKey' => 'title',
            'textTemplateKey' => 'title_template',
            'extraFields' => ['href' => 'route', 'current' => 'current'],
        ],
    ];

    /**
     * Constructor de NavigationBuilderService.
     */
    public function __construct(
        private readonly ModuleRegistryInterface $moduleRegistry
    ) {}

    /**
     * Construye elementos de navegación basados en el tipo y configuración.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function buildNavigation(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        // Verificar que el tipo de navegación es válido
        if (!isset(self::NAV_TYPE_CONFIG[$navType])) {
            Log::warning("Tipo de navegación desconocido: {$navType}");

            return [];
        }

        // Resolver referencias en la configuración si existen
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        $resolvedConfig = $this->resolveConfigReferences($itemsConfig, $moduleConfig);

        // Obtener la configuración específica para el tipo de navegación
        $config = self::NAV_TYPE_CONFIG[$navType];

        return $this->buildItems(
            $resolvedConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName,
            $config['textKey'],
            $config['textTemplateKey'],
            $config['extraFields']
        );
    }

    /**
     * Método de compatibilidad para buildContextualNavItems
     */
    public function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->buildNavigation(
            self::NAV_TYPE_CONTEXTUAL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * Construye los ítems del panel principal.
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @return array<int, array<string, mixed>>
     */
    public function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return $this->buildNavigation(
            self::NAV_TYPE_PANEL,
            $itemsConfig,
            $permissionChecker,
            $moduleSlug,
            $functionalName
        );
    }

    /**
     * Método genérico para construir ítems (de navegación o de panel).
     *
     * @param  array<int, array<string, mixed>>  $itemsConfig
     * @param  array<string, string>  $extraFields
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName,
        string $textKey,
        string $textTemplateKey,
        array $extraFields
    ): array {
        $builtItems = [];

        foreach ($itemsConfig as $config) {
            // Validación previa según tipo de item
            $errors = [];
            if ($textKey === 'name') {
                $errors = PanelItem::validate($config);
            } elseif ($textKey === 'title') {
                $errors = ContextualNavItem::validate($config);
            }
            if (!empty($errors)) {
                Log::warning('Configuración de item inválida', [
                    'module' => $moduleSlug,
                    'errors' => $errors,
                    'config' => $config,
                ]);
                // Ignorar ítems inválidos para no romper la vista
                continue;
            }

            $permission = $config['permission'] ?? null;
            // Soporte para string o array de permisos (permite si el usuario tiene CUALQUIERA de los permisos proporcionados)
            if ($permission) {
                $allowed = true;
                if (is_array($permission)) {
                    $allowed = false;
                    foreach ($permission as $perm) {
                        if (is_string($perm) && $permissionChecker($perm)) {
                            $allowed = true;
                            break;
                        }
                    }
                } elseif (is_string($permission)) {
                    $allowed = $permissionChecker($permission);
                }

                if (!$allowed) {
                    continue;
                }
            }

            // Determinar el texto a mostrar
            $text = $config[$textKey] ?? null;
            if (isset($config[$textTemplateKey]) && $functionalName) {
                $text = sprintf($config[$textTemplateKey], $functionalName);
            }

            // Construir la ruta (preferir route_name si está presente, si no, usar route_name_suffix)
            $routeName = $config['route_name'] ?? null;
            if (!$routeName) {
                $routeNameSuffix = $config['route_name_suffix'] ?? null;
                $routeName = $routeNameSuffix
                    ? "internal.{$moduleSlug}.{$routeNameSuffix}"
                    : "internal.{$moduleSlug}";
            }

            // Obtener los parámetros de ruta (preferir 'route_params' alias si existe)
            $routeParameters = [];
            if (isset($config['route_params']) && is_array($config['route_params'])) {
                $routeParameters = $config['route_params'];
            } elseif (isset($config['route_parameters']) && is_array($config['route_parameters'])) {
                $routeParameters = $config['route_parameters'];
            }

            // Crear el ítem base
            $item = [
                $textKey => $text,
                'icon' => $config['icon'] ?? null,
                'permission' => $permission,
            ];

            // Agregar campos extra según el tipo
            foreach ($extraFields as $fieldKey => $configKey) {
                $item[$fieldKey] = match ($configKey) {
                    'route' => $this->generateRoute($routeName, $routeParameters),
                    'current' => $this->isCurrentRoute($routeName),
                    'route_name' => $routeName,
                    default => $config[$configKey] ?? null,
                };
            }

            $builtItems[] = $item;
        }

        return $builtItems;
    }

    /**
     * Genera una URL de ruta de forma segura.
     */
    private function generateRoute(string $routeName, array $parameters = []): string
    {
        try {
            if (Route::has($routeName)) {
                // Verificar si la ruta requiere parámetros
                $route = Route::getRoutes()->getByName($routeName);
                if ($route) {
                    $paramNames = [];
                    // Extraer nombres de parámetros de la URI
                    preg_match_all('/\{([^\}]+)\}/', $route->uri(), $matches);
                    if (isset($matches[1])) {
                        $paramNames = $matches[1];
                    }

                    // Verificar si faltan parámetros requeridos
                    $missingParams = [];
                    foreach ($paramNames as $param) {
                        // Ignorar parámetros opcionales (con ?)
                        if (!str_contains($param, '?') && !isset($parameters[$param])) {
                            $missingParams[] = $param;
                        }
                    }

                    // Si faltan parámetros, no generar la ruta
                    if (!empty($missingParams)) {
                        return '#';
                    }
                }

                return route($routeName, $parameters);
            }
        } catch (\Exception $e) {
            // Solo registrar errores críticos en producción
            if (app()->environment('production')) {
                Log::error("Error al generar ruta {$routeName}", [
                    'exception' => get_class($e),
                    'route_name' => $routeName,
                    'parameters' => $parameters,
                ]);
            }
        }

        return '#';
    }

    /**
     * Construye los ítems de navegación para los módulos visibles en la barra lateral.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildNavItems(array $modules, callable $permissionChecker): array
    {
        $navItems = [];
        $moduleItems = [];

        foreach ($modules as $module) {
            $moduleName = strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);

            // Verificar si el módulo debe mostrarse en la navegación
            if (empty($config) || !($config['nav_item']['show_in_nav'] ?? false)) {
                continue;
            }

            // Verificar permiso
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;

            if (!$permission || $permissionChecker($permission)) {
                $routeName = $navItem['route_name'];
                $item = [
                    'title' => $config['functional_name'] ?? $moduleName,
                    'href' => $this->generateRoute($routeName),
                    'icon' => $navItem['icon'] ?? null,
                    'current' => $this->isCurrentRoute($routeName),
                ];

                // Determinar si el módulo debe aparecer en la navegación principal o en la sección de módulos
                if ($navItem['show_in_main_nav'] ?? false) {
                    $navItems[] = $item;
                } else {
                    $moduleItems[] = $item;
                }
            }
        }

        // Devolver solo los ítems de navegación principal
        // Los ítems de módulos se pasarán como contextualNavItems
        return $navItems;
    }

    /**
     * Construye los ítems de navegación para los módulos que deben mostrarse en la sección de módulos.
     *
     * @param  array<Module>  $modules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleNavItems(array $modules, callable $permissionChecker): array
    {
        $moduleItems = [];

        foreach ($modules as $module) {
            $moduleName = strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleName);

            // Verificar si el módulo debe mostrarse en la navegación
            if (empty($config) || !($config['nav_item']['show_in_nav'] ?? false)) {
                continue;
            }

            // Verificar permiso
            $navItem = $config['nav_item'];
            $permission = $config['base_permission'] ?? null;

            if (!$permission || $permissionChecker($permission)) {
                // Solo incluir si no está marcado para la navegación principal
                if (!($navItem['show_in_main_nav'] ?? false)) {
                    $routeName = $navItem['route_name'];
                    $moduleItems[] = [
                        'title' => $config['functional_name'] ?? $moduleName,
                        'href' => $this->generateRoute($routeName),
                        'icon' => $navItem['icon'] ?? null,
                        'current' => $this->isCurrentRoute($routeName),
                    ];
                }
            }
        }

        return $moduleItems;
    }

    /**
     * Construye las tarjetas de módulos para el dashboard.
     *
     * @param  array<Module>  $allModules
     * @param  array<Module>  $accessibleModules
     * @return array<int, array<string, mixed>>
     */
    public function buildModuleCards(array $allModules, array $accessibleModules = []): array
    {
        $moduleCards = [];

        // Set de nombres de módulos accesibles en minúsculas para comparación rápida
        $accessibleNames = [];
        foreach ($accessibleModules as $am) {
            $accessibleNames[strtolower($am->getName())] = true;
        }

        foreach ($allModules as $module) {
            $moduleNameLower = strtolower($module->getName());
            $config = $this->moduleRegistry->getModuleConfig($moduleNameLower);

            if (empty($config) || !isset($config['nav_item'])) {
                continue;
            }

            $routeName = $config['nav_item']['route_name'];
            $canAccess = isset($accessibleNames[$moduleNameLower]);

            $moduleCards[] = [
                'name' => $config['functional_name'] ?? $module->getName(),
                'description' => $config['description'] ?? '',
                'href' => $this->generateRoute($routeName),
                'icon' => $config['nav_item']['icon'] ?? null,
                'canAccess' => $canAccess,
            ];
        }

        return $moduleCards;
    }

    /**
     * Verifica si la ruta actual coincide con el patrón dado.
     */
    public function isCurrentRoute(string $routeName): bool
    {
        $currentRoute = Route::currentRouteName();

        if (!$currentRoute) {
            return false;
        }

        // Verificar coincidencia exacta o si la ruta actual comienza con el nombre de la ruta base
        return $currentRoute === $routeName ||
            str_starts_with($currentRoute, "{$routeName}.");
    }

    /**
     * Construye los breadcrumbs a partir de una configuración explícita.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildConfiguredBreadcrumbs(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);

        // Verificar si existen breadcrumbs configurados para esta ruta
        if (!isset($moduleConfig['breadcrumbs'][$routeSuffix])) {
            // Si no hay configuración específica, devolvemos al menos el breadcrumb del módulo principal
            return [[
                'title' => $moduleConfig['functional_name'] ?? ucfirst($moduleSlug),
                'href' => $this->generateRoute("internal.{$moduleSlug}.panel"),
            ]];
        }

        // Obtener la configuración de breadcrumbs y resolver las referencias
        $breadcrumbsConfig = $moduleConfig['breadcrumbs'][$routeSuffix];
        $resolvedBreadcrumbsConfig = $this->resolveConfigReferences($breadcrumbsConfig, $moduleConfig, $routeParams);

        $breadcrumbs = [];

        foreach ($resolvedBreadcrumbsConfig as $config) {
            $title = $config['title'] ?? '';

            // Manejar títulos dinámicos (acepta dynamic_title y dynamic_title_prop)
            $dynamicKey = isset($config['dynamic_title']) ? 'dynamic_title' : (isset($config['dynamic_title_prop']) ? 'dynamic_title_prop' : null);
            if ($dynamicKey && !empty($config[$dynamicKey])) {
                $dynamicTitle = $this->extractDynamicTitle($config[$dynamicKey], $viewData);
                if ($dynamicTitle !== null) {
                    $title = $title . ': ' . $dynamicTitle;
                }
            }

            // Determinar href con prioridad: href directo -> route_name/route_name_suffix -> '#'
            if (isset($config['href']) && is_string($config['href']) && $config['href'] !== '') {
                $href = $config['href'];
            } else {
                $routeName = $config['route_name'] ?? null;
                if (!$routeName && isset($config['route_name_suffix'])) {
                    $routeName = "internal.{$moduleSlug}." . $config['route_name_suffix'];
                }

                // Preferir route_params, luego route_parameters (de configs con placeholders)
                $routeParams = [];
                if (isset($config['route_params']) && is_array($config['route_params'])) {
                    $routeParams = $config['route_params'];
                } elseif (isset($config['route_parameters']) && is_array($config['route_parameters'])) {
                    $routeParams = $config['route_parameters'];
                }

                $href = $routeName ? $this->generateRoute($routeName, $routeParams) : '#';
            }

            $breadcrumbs[] = [
                'title' => $title,
                'href' => $href,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Extrae un valor dinámico de los datos de la vista usando notación de punto.
     *
     * @param  string  $path  Ruta al valor (formato: 'usuario.name')
     * @param  array<string, mixed>  $data  Datos de donde extraer el valor
     * @return string|null El valor extraído o null si no se encuentra
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
     * Resuelve referencias en la configuración del formato '$ref:path.to.component'.
     *
     * @param  mixed  $item  Configuración con posibles referencias
     * @param  array<string, mixed>  $config  Configuración completa del módulo
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return mixed Configuración con referencias resueltas
     */
    public function resolveConfigReferences(mixed $item, array $config, array $routeParams = []): mixed
    {
        // Si es un string y comienza con '$ref:', resolverlo
        if (is_string($item) && str_starts_with($item, '$ref:')) {
            $path = substr($item, 5); // Remover '$ref:'
            $parts = explode('.', $path);
            $value = $config;

            // Intentar primero la ruta directa
            $directPathFound = true;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    $directPathFound = false;
                    break;
                }
                $value = $value[$part];
            }

            // Si la ruta directa no funciona, intentar buscar en ubicaciones alternativas
            if (!$directPathFound) {
                // Caso 1: Referencias a links (ej: $ref:links.panel)
                if (count($parts) >= 2 && $parts[0] === 'links') {
                    // Construir la ruta alternativa con nav_components.links
                    $alternativePath = ['nav_components', 'links', $parts[1]];

                    // Añadir cualquier parte adicional después de links.xxx
                    if (count($parts) > 2) {
                        $alternativePath = array_merge($alternativePath, array_slice($parts, 2));
                    }

                    // Intentar la ruta alternativa
                    $value = $config;
                    $alternativeFound = true;
                    foreach ($alternativePath as $part) {
                        if (!isset($value[$part])) {
                            $alternativeFound = false;
                            break;
                        }
                        $value = $value[$part];
                    }

                    if ($alternativeFound) {
                        $directPathFound = true; // Marcar como encontrado
                    }
                }

                // Caso 2: Referencias a grupos (ej: $ref:groups.user_management)
                if (!$directPathFound && count($parts) >= 2 && $parts[0] === 'groups') {
                    // Construir la ruta alternativa con nav_components.groups
                    $alternativePath = ['nav_components', 'groups', $parts[1]];

                    // Añadir cualquier parte adicional después de groups.xxx
                    if (count($parts) > 2) {
                        $alternativePath = array_merge($alternativePath, array_slice($parts, 2));
                    }

                    // Intentar la ruta alternativa
                    $value = $config;
                    $alternativeFound = true;
                    foreach ($alternativePath as $part) {
                        if (!isset($value[$part])) {
                            $alternativeFound = false;
                            break;
                        }
                        $value = $value[$part];
                    }

                    if ($alternativeFound) {
                        $directPathFound = true; // Marcar como encontrado
                    }
                }
            }

            // Si ninguna ruta funcionó, registrar advertencia
            if (!$directPathFound) {
                Log::warning("Referencia no encontrada: {$item} (intentadas rutas alternativas)");

                return $item;
            }

            // Si encontramos un array, resolver referencias recursivamente
            if (is_array($value)) {
                return $this->resolveConfigReferences($value, $config, $routeParams);
            }

            // Si el valor es una ruta y tenemos parámetros, aplicarlos
            if (is_string($value) && str_starts_with($value, 'internal.') && !empty($routeParams)) {
                // Generar la URL con los parámetros proporcionados
                return $this->generateRoute($value, $routeParams);
            }

            return $value;
        }

        // Si es un array asociativo, verificar si tiene route_parameters y aplicar los parámetros de ruta
        if (is_array($item) && $this->isAssociativeArray($item) && isset($item['route_parameters']) && !empty($routeParams)) {
            // Procesar los parámetros de ruta
            $processedRouteParams = [];
            foreach ($item['route_parameters'] as $key => $value) {
                // Si el valor comienza con ':', buscar el valor real en $routeParams
                if (is_string($value) && str_starts_with($value, ':')) {
                    $paramName = substr($value, 1); // Remover ':'
                    if (isset($routeParams[$paramName])) {
                        $processedRouteParams[$key] = $routeParams[$paramName];
                    } else {
                        // Si no se encuentra el parámetro, mantener el valor original
                        $processedRouteParams[$key] = $value;
                    }
                } else {
                    // Si no es un marcador de posición, mantener el valor original
                    $processedRouteParams[$key] = $value;
                }
            }

            // Actualizar los parámetros de ruta en el ítem
            $item['route_parameters'] = $processedRouteParams;
        }

        // Si es un array, resolver recursivamente cada elemento
        if (is_array($item)) {
            return $this->isAssociativeArray($item)
                ? $this->resolveAssociativeArray($item, $config, $routeParams)
                : $this->resolveSequentialArray($item, $config, $routeParams);
        }

        // Para cualquier otro tipo, devolver tal como está
        return $item;
    }

    /**
     * Resuelve referencias en un array asociativo.
     *
     * @param  array<string|int, mixed>  $array  Array asociativo a resolver
     * @param  array<string, mixed>  $config  Configuración completa
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return array<string|int, mixed> Array con referencias resueltas
     */
    private function resolveAssociativeArray(array $array, array $config, array $routeParams = []): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $resolvedValue = $this->resolveConfigReferences($value, $config, $routeParams);

            // Si no es una clave string y el valor es un array secuencial, aplanarlo
            if (!is_string($key) && is_array($resolvedValue) && !$this->isAssociativeArray($resolvedValue)) {
                foreach ($resolvedValue as $subValue) {
                    $result[] = $subValue;
                }
            } else {
                $result[$key] = $resolvedValue;
            }
        }

        return $result;
    }

    /**
     * Resuelve referencias en un array secuencial (numérico).
     *
     * @param  array<int, mixed>  $array  Array secuencial a resolver
     * @param  array<string, mixed>  $config  Configuración completa
     * @param  array<string, mixed>  $routeParams  Parámetros adicionales para las rutas
     * @return array<int, mixed> Array con referencias resueltas
     */
    private function resolveSequentialArray(array $array, array $config, array $routeParams = []): array
    {
        $result = [];

        foreach ($array as $value) {
            $resolvedValue = $this->resolveConfigReferences($value, $config, $routeParams);

            // Si el valor es un array secuencial, aplanarlo en el resultado
            if (is_array($resolvedValue) && !$this->isAssociativeArray($resolvedValue)) {
                foreach ($resolvedValue as $subValue) {
                    $result[] = $subValue;
                }
            } else {
                $result[] = $resolvedValue;
            }
        }

        return $result;
    }

    /**
     * Determina si un array es asociativo.
     */
    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Método de compatibilidad para buildBreadcrumbsFromContextual
     */
    public function buildBreadcrumbsFromContextual(array $contextualItems, string $moduleSlug): array
    {
        // Usar la lógica de la ruta actual para determinar el suffix
        $currentRoute = Route::currentRouteName();
        if (!$currentRoute || !str_starts_with($currentRoute, "internal.{$moduleSlug}.")) {
            return [[
                'title' => ucfirst($moduleSlug),
                'href' => $this->generateRoute("internal.{$moduleSlug}.panel"),
            ]];
        }

        $routeSuffix = substr($currentRoute, strlen("internal.{$moduleSlug}."));

        // Intentar usar la configuración si existe
        $moduleConfig = $this->moduleRegistry->getModuleConfig($moduleSlug);
        if (isset($moduleConfig['breadcrumbs'][$routeSuffix])) {
            return $this->buildConfiguredBreadcrumbs($moduleSlug, $routeSuffix);
        }

        // Construir breadcrumbs a partir de los ítems contextuales (implementación básica)
        $breadcrumbs = [];

        // Agregar el primer ítem como principal
        if (!empty($contextualItems) && isset($contextualItems[0])) {
            $breadcrumbs[] = [
                'title' => $contextualItems[0]['title'] ?? ucfirst($moduleSlug),
                'href' => $contextualItems[0]['href'] ?? '#',
            ];

            // Buscar el ítem activo como segundo nivel
            foreach ($contextualItems as $item) {
                if (
                    isset($item['current']) && $item['current'] === true &&
                    (!isset($breadcrumbs[0]) || $breadcrumbs[0]['title'] !== $item['title'])
                ) {
                    $breadcrumbs[] = [
                        'title' => $item['title'] ?? '',
                        'href' => $item['href'] ?? '#',
                    ];
                    break;
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Método de compatibilidad para buildGlobalNavItems
     */
    public function buildGlobalNavItems(array $itemsConfig, callable $permissionChecker): array
    {
        // Procesar cada ítem global
        $items = [];

        foreach ($itemsConfig as $config) {
            $permission = $config['permission'] ?? null;

            if ($permission && !$permissionChecker($permission)) {
                continue;
            }

            // Crear el ítem base
            $item = [
                'title' => $config['title'] ?? '',
                'icon' => $config['icon'] ?? null,
                'permission' => $permission,
            ];

            // Determinar la URL: usar href directo si está presente, de lo contrario generar ruta
            if (isset($config['href'])) {
                $item['href'] = $config['href'];
                $item['current'] = $this->isCurrentUrl($config['href']);
            } elseif (isset($config['route_name'])) {
                $routeName = $config['route_name'];
                $routeParameters = isset($config['route_params']) && is_array($config['route_params'])
                    ? $config['route_params']
                    : ($config['route_parameters'] ?? []);
                $item['href'] = $this->generateRoute($routeName, $routeParameters);
                $item['current'] = $this->isCurrentRoute($routeName);
            } else {
                $item['href'] = '#';
                $item['current'] = false;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Verifica si la URL actual coincide con la dada.
     */
    private function isCurrentUrl(string $url): bool
    {
        if ($url === '#') {
            return false;
        }

        $currentUrl = url()->current();

        return $currentUrl === $url;
    }

    /**
     * Ensambla la estructura de navegación completa para una vista.
     *
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  string|null  $moduleSlug  Slug del módulo actual (null para dashboard principal)
     * @param  array<int, array<string, mixed>>  $contextualItemsConfig  Configuración de ítems contextuales
     * @param  mixed  $user  Usuario autenticado
     * @param  string|null  $functionalName  Nombre funcional del módulo
     * @param  string|null  $routeSuffix  Sufijo de ruta para breadcrumbs
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta para breadcrumbs
     * @param  array<string, mixed>  $viewData  Datos de la vista para títulos dinámicos
     * @return array<string, mixed> Estructura de navegación completa
     */
    public function assembleNavigationStructure(
        callable $permissionChecker,
        ?string $moduleSlug = null,
        array $contextualItemsConfig = [],
        $user = null,
        ?string $functionalName = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $viewData = []
    ): array {
        // Obtener los módulos habilitados
        $modules = $this->moduleRegistry->getAccessibleModules($user);

        // Construir los ítems de navegación principal
        $mainNavItems = $this->buildNavItems($modules, $permissionChecker);

        // Construir los ítems de navegación de módulos
        $moduleNavItems = $this->buildModuleNavItems($modules, $permissionChecker);

        // Construir los ítems de navegación contextual
        $contextualNavItems = [];
        if (!empty($contextualItemsConfig)) {
            $contextualNavItems = $this->buildContextualNavItems(
                $contextualItemsConfig,
                $permissionChecker,
                $moduleSlug ?? '',
                $functionalName
            );
        }

        // Construir los ítems de navegación global (solo para el dashboard principal)
        $globalNavItems = [];
        if ($moduleSlug === null) {
            $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($user);
            $globalNavItems = $this->buildGlobalNavItems($globalItemsConfig, $permissionChecker);
        }

        // Construir breadcrumbs
        $breadcrumbs = [];
        if ($moduleSlug && $routeSuffix) {
            $breadcrumbs = $this->buildConfiguredBreadcrumbs(
                $moduleSlug,
                $routeSuffix,
                $routeParams,
                $viewData
            );
        } elseif (!empty($contextualNavItems)) {
            $breadcrumbs = $this->buildBreadcrumbsFromContextual(
                $contextualNavItems,
                $moduleSlug ?? ''
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
     * Extrae el sufijo de ruta de una ruta completa.
     */
    private function extractRouteSuffix(?string $routeName, string $moduleSlug): string
    {
        if ($routeName && str_starts_with($routeName, "internal.{$moduleSlug}.")) {
            return substr($routeName, strlen("internal.{$moduleSlug}."));
        }

        // Por defecto, usar 'panel'
        return 'panel';
    }
}
