<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Interfaces\ModuleRegistryInterface;
use App\Interfaces\NavigationBuilderInterface;
use App\Interfaces\ViewComposerInterface;
use App\Traits\PermissionVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;

/**
 * Controlador base para todos los módulos del sistema.
 * Proporciona funcionalidades comunes para orquestar la renderización de vistas,
 * gestión de navegación y verificación de permisos.
 */
abstract class ModuleOrchestrationController extends Controller
{
    use PermissionVerifier;

    /**
     * Clave identificadora del módulo (slug), autodetectada del namespace.
     */
    protected string $moduleSlug = '';

    /**
     * Configuración del módulo cargada desde el archivo de configuración.
     *
     * @var array<string, mixed>
     */
    protected array $moduleConfig = [];

    /**
     * Constructor del controlador base para módulos.
     *
     * @param  ModuleRegistryInterface  $moduleRegistryService  Servicio para gestionar el registro y acceso a los módulos.
     * @param  ViewComposerInterface  $viewComposerService  Servicio para componer y preparar los datos para las vistas.
     * @param  NavigationBuilderInterface|null  $navigationService  Servicio para construir la navegación (opcional).
     */
    public function __construct(
        protected readonly ModuleRegistryInterface $moduleRegistryService,
        protected readonly ViewComposerInterface $viewComposerService,
        protected readonly ?NavigationBuilderInterface $navigationService = null
    ) {
        // Autodetectar slug y cargar configuración del módulo basado en el namespace.
        $this->detectModuleAndLoadConfig();
    }

    /**
     * Detecta el slug del módulo desde el namespace y carga su configuración.
     */
    private function detectModuleAndLoadConfig(): void
    {
        // Obtiene el namespace completo de la clase hija que está siendo instanciada.
        $namespace = (new \ReflectionClass(static::class))->getNamespaceName();

        // Divide el namespace en partes para extraer el nombre del módulo.
        $parts = explode('\\', $namespace);

        // El nombre del módulo debe ser el segundo elemento (índice 1).
        // e.g., Modules\Admin\Http\Controllers -> 'Admin'
        if ($parts[0] === 'Modules' && isset($parts[1])) {
            // El slug del módulo es el segundo elemento, en minúsculas.
            $this->moduleSlug = strtolower($parts[1]);
            $this->moduleConfig = $this->moduleRegistryService->getModuleConfig($this->moduleSlug);
        }
    }

    /**
     * Obtiene el slug del módulo.
     */
    protected function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    /**
     * Obtiene el nombre funcional del módulo desde la configuración.
     */
    protected function getFunctionalName(): string
    {
        return $this->moduleConfig['functional_name'] ?? '';
    }

    /**
     * Obtiene el directorio de vistas de Inertia.
     */
    protected function getInertiaViewDirectory(): string
    {
        return $this->moduleConfig['inertia_view_directory'] ?? $this->moduleSlug;
    }

    /**
     * Obtiene el/los permisos base del módulo.
     */
    protected function getBaseAccessPermission(): string|array
    {
        return $this->moduleConfig['base_permission'] ?? '';
    }

    /**
     * Obtiene el guard de autenticación a utilizar.
     */
    protected function getAuthGuard(): string
    {
        return $this->moduleConfig['auth_guard'] ?? '';
    }

    /**
     * Devuelve el usuario autenticado usando el guard especificado.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getAuthenticatedUser()
    {
        return Auth::guard($this->getAuthGuard())->user();
    }

    /**
     * Obtiene la configuración de los ítems del panel principal del módulo.
     * Implementación por defecto que lee desde la configuración del módulo.
     * Los controladores hijos pueden sobrescribir si necesitan lógica adicional.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPanelItemsConfig(): array
    {
        $panelConfig = $this->moduleConfig['panel_items'] ?? [];

        return is_array($panelConfig) ? $panelConfig : [];
    }

    /**
     * Obtiene la configuración de los ítems de navegación contextual para el módulo.
     * Implementación por defecto que intenta:
     * 1) Detectar dinámicamente el sufijo de la ruta actual y usar esa clave si existe.
     * 2) En caso de no existir una clave específica, usar la clave 'default' como fallback.
     * Los controladores hijos pueden sobrescribir si necesitan lógica adicional específica.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getContextualNavItemsConfig(): array
    {
        $navConfigAll = $this->moduleConfig['contextual_nav'] ?? [];

        if (!is_array($navConfigAll)) {
            return [];
        }

        // Intentar usar configuración específica por la ruta actual
        try {
            $currentRequest = request();
            if ($currentRequest && $currentRequest->route()) {
                $suffix = $this->extractRouteSuffixFromRequest($currentRequest);
                if (isset($navConfigAll[$suffix]) && is_array($navConfigAll[$suffix])) {
                    return $navConfigAll[$suffix];
                }
            }
        } catch (\Throwable $e) {
            // Si no se puede detectar, continuar con fallback silenciosamente
        }

        // Fallback a 'default' si está disponible
        if (isset($navConfigAll['default']) && is_array($navConfigAll['default'])) {
            return $navConfigAll['default'];
        }

        return [];
    }

    /**
     * Muestra el panel principal del módulo.
     */
    public function showModulePanel(Request $request): InertiaResponse
    {
        $additional = ['stats' => $this->getModuleStats()];
        $extras = $this->getAdditionalPanelData();
        if (is_array($extras) && !empty($extras)) {
            $additional = array_merge($additional, $extras);
        }

        return $this->prepareAndRenderModuleView(
            view: 'index',
            request: $request,
            additionalData: $additional
        );
    }

    /**
     * Define las estadísticas para el panel del módulo (opcional).
     *
     * @return array<string, mixed>|null
     */
    protected function getModuleStats(): ?array
    {
        return null; // Por defecto no hay estadísticas
    }

    /**
     * Permite a cada módulo aportar datos adicionales para el panel.
     * Por defecto no añade información.
     *
     * @return array<string, mixed>
     */
    protected function getAdditionalPanelData(): array
    {
        return [];
    }

    /**
     * Renderiza una vista de Inertia específica del módulo con datos comunes.
     *
     * @param  array<string, mixed>  $data
     */
    protected function renderModuleView(string $view, array $data = []): InertiaResponse
    {
        // Renderizar la vista usando el servicio, que se encarga de construir la ruta correcta.
        return $this->viewComposerService->renderModuleView(
            view: $view,
            moduleViewPath: $this->getInertiaViewDirectory(),
            data: $data
        );
    }
    /**
     * Resuelve referencias en la configuración del módulo.
     * Utiliza el servicio de navegación para resolver referencias del tipo '$ref:path.to.component'.
     *
     * @param  mixed  $config  Configuración que puede contener referencias
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta actuales para resolver placeholders dinámicos
     * @return mixed Configuración con referencias resueltas
     */
    protected function resolveConfigReferences(mixed $config, array $routeParams = []): mixed
    {
        if ($this->navigationService && !empty($config)) {
            // Forward route params to allow dynamic resolution in breadcrumbs and nav
            return $this->navigationService->resolveConfigReferences($config, $this->moduleConfig, $routeParams);
        }

        return $config;
    }

    /**
     * Prepara el contexto completo para una vista de módulo y la renderiza.
     * Este método encapsula el patrón común de preparación y renderizado de vistas.
     * Siempre incluye pageTitle (functional_name), description (desde config) y breadcrumbs.
     *
     * @param  string  $view  Nombre de la vista a renderizar.
     * @param  Request  $request  Request actual para obtener el usuario y permisos.
     * @param  array<string, mixed>  $additionalData  Datos adicionales específicos de la vista.
     * @param  array<int, array<string, mixed>>|null  $customPanelItems  Items personalizados del panel (opcional).
     * @param  array<int, array<string, mixed>>|null  $customNavItems  Items personalizados de navegación contextual (opcional).
     * @param  string|null  $routeSuffix  Sufijo de la ruta actual (opcional, se detectará automáticamente si es null).
     * @param  array<string, mixed>  $routeParams  Parámetros de la ruta para los breadcrumbs (opcional).
     * @param  array<string, mixed>  $dynamicTitleData  Datos específicos para títulos dinámicos en breadcrumbs (opcional).
     * @return InertiaResponse Respuesta Inertia con el contexto completo.
     */
    protected function prepareAndRenderModuleView(
        string $view,
        Request $request,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = []
    ): InertiaResponse {
        // Obtener el usuario y verificar autenticación
        $user = $request->user($this->getAuthGuard())
            ?: abort(403, 'Usuario no autenticado');

        // Si no se proporcionaron parámetros de ruta, intentar obtenerlos de la solicitud actual
        if (empty($routeParams)) {
            $routeParams = $request->route()->parameters();
        }

        // Obtener la configuración de navegación del módulo
        $panelItemsConfig = $customPanelItems ?? $this->getPanelItemsConfig();
        $contextualNavItemsConfig = $customNavItems ?? $this->getContextualNavItemsConfig();

        // Resolver referencias en las configuraciones
        if ($this->navigationService) {
            $panelItemsConfig = $this->resolveConfigReferences($panelItemsConfig, $routeParams);
            $contextualNavItemsConfig = $this->resolveConfigReferences($contextualNavItemsConfig, $routeParams);
        }

        $functionalName = $this->getFunctionalName();

        // Determinar el sufijo de ruta si no se proporcionó
        $routeSuffix ??= $this->extractRouteSuffixFromRequest($request);

        // Combinar datos de título dinámico con datos adicionales
        $viewData = array_merge($additionalData, $dynamicTitleData);

        // Preparar el contexto de la vista usando el servicio
        $viewContext = $this->viewComposerService->composeModuleViewContext(
            moduleSlug: $this->moduleSlug,
            panelItemsConfig: $panelItemsConfig,
            contextualNavItemsConfig: $contextualNavItemsConfig,
            permissionChecker: fn(string $permission) => $user->hasPermissionTo($permission),
            user: $user,
            functionalName: $functionalName,
            data: $viewData,
            stats: $additionalData['stats'] ?? null,
            routeSuffix: $routeSuffix,
            routeParams: $routeParams
        );

        return $this->renderModuleView($view, $viewContext);
    }

    /**
     * Extrae el sufijo de ruta del objeto Request.
     */
    private function extractRouteSuffixFromRequest(Request $request): string
    {
        $currentRoute = $request->route()->getName();

        // Si la ruta actual pertenece al módulo, extraer el sufijo
        if ($currentRoute && str_starts_with($currentRoute, "internal.{$this->moduleSlug}.")) {
            return substr($currentRoute, strlen("internal.{$this->moduleSlug}."));
        }

        // Por defecto, usar 'panel' o el último segmento de la ruta actual
        $parts = explode('.', $currentRoute);

        return end($parts) ?: 'panel';
    }
}
