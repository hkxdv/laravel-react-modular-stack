<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\NavigationComposerInterface;
use Modules\Core\Contracts\ViewComposerInterface;

/**
 * Servicio para componer y preparar datos para las vistas (Laravel/Inertia).
 *
 * Estandariza props compartidas y estructura de navegación con caché
 * versionada por usuario/ruta y estado de módulos.
 */
final readonly class ViewComposerService implements ViewComposerInterface
{
    public function __construct(
        private MenuBuilderInterface $navigationService,
        private AddonRegistryInterface $moduleRegistry,
        private NavigationComposerInterface $navigationComposer,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Normaliza items del panel asegurando claves string.
     */
    public function prepareModuleViewData(
        string $moduleSlug,
        array $panelItemsConfig,
        callable $permissionChecker,
        string $functionalName,
        ?array $stats = null,
        array $data = []
    ): array {
        $isList = array_is_list($panelItemsConfig);

        $normalizedPanelItemsConfig = $isList
            ? $panelItemsConfig
            : [$panelItemsConfig];

        // Asegurar que cada ítem tenga llaves string para cumplir el contrato
        $normalizedPanelItemsConfig = array_map(
            static function ($item): array {
                if (! is_array($item)) {
                    return [];
                }

                $assoc = [];
                foreach ($item as $k => $v) {
                    $assoc[(string) $k] = $v;
                }

                return $assoc;
            },
            $normalizedPanelItemsConfig
        );

        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $normalizedPanelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        // Obtener descripción desde el config del módulo
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);
        $moduleDescription = $moduleConfig['description'] ?? null;

        $statsList = is_array($stats)
            ? array_values($stats)
            : [];

        return [
            ...[
                'panelItems' => $panelItems,
                'stats' => $statsList,
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * Nota: Aplica caché versionada con claves que incluyen:
     * usuario, módulo, routeSuffix, nav_version, mtime de módulos y perm_version.
     */
    public function composeModuleViewContext(
        string $moduleSlug,
        array $panelItemsConfig,
        array $contextualNavItemsConfig,
        callable $permissionChecker,
        ?Authenticatable $user,
        ?string $functionalName = null,
        array $data = [],
        ?array $stats = null,
        ?string $routeSuffix = null,
        array $routeParams = []
    ): array {
        // Normalizar nombre funcional y obtener descripción desde config del módulo
        $moduleConfig = $this->moduleRegistry->getAddonConfig($moduleSlug);
        $fn = $moduleConfig['functional_name'] ?? null;
        $functionalName = is_string($functionalName)
            ? $functionalName
            : (is_string($fn)
                ? $fn
                : ucfirst($moduleSlug)
            );
        $moduleDescription = $moduleConfig['description'] ?? null;

        $suffix = is_string($routeSuffix) && $routeSuffix !== ''
            ? $routeSuffix
            : 'panel';

        // Delegar composición de navegación con caché versionada
        $navigationElements = $this->navigationComposer->composeNavigation(
            moduleSlug: $moduleSlug,
            contextualNavItemsConfig: $contextualNavItemsConfig,
            permissionChecker: $permissionChecker,
            user: $user,
            functionalName: $functionalName,
            routeSuffix: $suffix,
            routeParams: $routeParams,
            data: $data
        );

        // Construir ítems del panel
        $panelItems = $this->navigationService
            ->buildPanelItems(
                itemsConfig: $panelItemsConfig,
                permissionChecker: $permissionChecker,
                moduleSlug: $moduleSlug,
                functionalName: $functionalName
            );

        $statsList = is_array($stats)
            ? array_values($stats)
            : [];

        // Combinar todos los datos
        return [
            ...[
                'panelItems' => $panelItems,
                'mainNavItems' => $navigationElements['mainNavItems'] ?? [],
                'moduleNavItems' => $navigationElements['moduleNavItems'] ?? [],
                'contextualNavItems' => $navigationElements['contextualNavItems'] ?? [],
                'globalNavItems' => $navigationElements['globalNavItems'] ?? [],
                'breadcrumbs' => $navigationElements['breadcrumbs'] ?? [],
                'stats' => $statsList,
                'pageTitle' => $functionalName,
                'description' => $moduleDescription,
                'flash' => $this->getFlashMessages(request()),
            ],
            ...$data,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function composeDashboardViewContext(
        ?Authenticatable $user,
        array $availableModules,
        callable $permissionChecker,
        Request $request
    ): array {
        /** @var \Modules\Core\Infrastructure\Eloquent\Models\StaffUser|null $user */
        /** @var array<\Nwidart\Modules\Laravel\Module> $availableModules */

        // Construir los ítems de navegación principales
        $mainNavItems = $this->navigationService->buildNavItems(
            $availableModules,
            $permissionChecker
        );

        $moduleNavItems = $this->navigationService->buildModuleNavItems(
            $availableModules,
            $permissionChecker
        );

        // Construir items de navegación global
        $globalItemsConfig = $this->moduleRegistry->getGlobalNavItems($user);
        $globalNavItems = $this->navigationService->buildGlobalNavItems(
            $globalItemsConfig,
            $permissionChecker
        );

        // Construir tarjetas de módulos (disponibles y restringidos)
        $allModules = $this->moduleRegistry->getAllEnabledAddons();
        $moduleCards = $this->navigationService->buildModuleCards(
            $allModules,
            $availableModules
        );

        $accessibleModulesCards = array_values(array_filter(
            $moduleCards,
            static fn (array $card): bool => ($card['canAccess'] ?? false) === true
        ));

        $restrictedModulesCards = array_values(array_filter(
            $moduleCards,
            static fn (array $card): bool => ($card['canAccess'] ?? false) === false
        ));

        return [
            'pageTitle' => 'Dashboard',
            'description' => 'Panel principal del sistema interno.',
            'breadcrumbs' => [[
                'title' => 'Dashboard',
                'href' => route('internal.staff.dashboard'),
            ]],
            'mainNavItems' => $mainNavItems,
            'moduleNavItems' => $moduleNavItems,
            'contextualNavItems' => [],
            'globalNavItems' => $globalNavItems,
            'modules' => $moduleCards,
            'accessibleModules' => $accessibleModulesCards,
            'restrictedModules' => $restrictedModulesCards,
            'flash' => $this->getFlashMessages($request),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function renderModuleView(
        string $view,
        string $moduleViewPath,
        array $data = []
    ): InertiaResponse {
        return Inertia::render(
            sprintf('modules/%s/%s', $moduleViewPath, $view),
            $data
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFlashMessages(Request $request): array
    {
        return [
            'success' => $request->session()->get('success'),
            'error' => $request->session()->get('error'),
            'info' => $request->session()->get('info'),
            'warning' => $request->session()->get('warning'),
            'credentials' => $request->session()->get('credentials'),
        ];
    }
}
