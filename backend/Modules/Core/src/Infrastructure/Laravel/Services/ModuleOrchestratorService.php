<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response as InertiaResponse;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Contracts\ViewComposerInterface;
use Modules\Core\Domain\User\DomainUser;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Mappers\DomainUserMapper;
use RuntimeException;

use function Foundry\Helpers\configArray;
use function Foundry\Helpers\configNullableString;

/**
 * Servicio de orquestación de vistas de módulos (implementación Laravel).
 *
 * Delegado en contratos de Core; normaliza resolución de usuario,
 * composición de contexto y referencias declarativas. Añade consistencia
 * de rutas y permisos cross-guard sin acoplar a Infrastructure en módulos.
 */
final readonly class ModuleOrchestratorService implements ModuleOrchestratorInterface
{
    /**
     * @param  AddonRegistryInterface  $addonRegistry  Registro de módulos y sus configuraciones.
     * @param  ViewComposerInterface  $viewComposer  Servicio de composición y renderizado Inertia.
     * @param  PermissionVerifierInterface  $permissionVerifier  Verificador de permisos cross-guard.
     */
    public function __construct(
        private AddonRegistryInterface $addonRegistry,
        private ViewComposerInterface $viewComposer,
        private PermissionVerifierInterface $permissionVerifier
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function resolveAuthenticatedUser(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null
    ): ?Authenticatable {
        $config = $this->resolveModuleConfig($moduleSlug, $moduleConfig);

        $guard = $config->addon()->authGuard;
        $user = null;
        $guardResolved = false;

        if ($guard !== null && $guard !== '') {
            $user = $request->user($guard);
            $guardResolved = true;
        }

        if (! $guardResolved) {
            $guardsArr = configArray('auth.guards');
            foreach (array_keys($guardsArr) as $candidateGuard) {
                if ($candidateGuard === '') {
                    continue;
                }

                if (Auth::guard($candidateGuard)->check()) {
                    $user = $request->user($candidateGuard);
                    $guardResolved = true;

                    break;
                }
            }
        }

        if (! $guardResolved) {
            $defaultGuardName = configNullableString('auth.defaults.guard');
            if ($defaultGuardName !== null) {
                $user = $request->user($defaultGuardName);
            }
        }

        return $user instanceof Authenticatable ? $user : null;
    }

    /**
     * {@inheritDoc}
     */
    public function renderModuleView(
        Request $request,
        string $moduleSlug,
        ?array $moduleConfig = null,
        array $additionalData = [],
        ?array $customPanelItems = null,
        ?array $customNavItems = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $dynamicTitleData = [],
        ?MenuBuilderInterface $navigationService = null,
        string $view = 'index'
    ): InertiaResponse {
        $config = $this->resolveModuleConfig($moduleSlug, $moduleConfig);

        $user = $this->resolveAuthenticatedUser($request, $moduleSlug, $moduleConfig)
          ?: abort(403, 'Usuario no autenticado');

        if ($routeParams === []) {
            $route = $request->route();
            $routeParams = $route ? $route->parameters() : [];
        }

        $normalizedRouteParams = [];
        foreach ($routeParams as $key => $value) {
            $normalizedRouteParams[(string) $key] = $value;
        }

        $routeParams = $normalizedRouteParams;

        // Leer panel_items y contextual_nav desde DTOs
        $panelItemsConfig = $customPanelItems ?? $this->panelItemsToConfig($config);
        $contextualNavItemsConfig = $customNavItems
          ?? $this->resolveContextualNavConfig($config, $moduleSlug, $request);

        /** @var array<int, array<string, mixed>> $panelItemsConfig */
        $panelItemsConfig = array_values(array_filter($panelItemsConfig, is_array(...)));
        /** @var array<int, array<string, mixed>> $contextualNavItemsConfig */
        $contextualNavItemsConfig = array_values(array_filter($contextualNavItemsConfig, is_array(...)));

        $functionalName = $config->addon()->functionalName !== ''
          ? $config->addon()->functionalName
          : null;

        $routeSuffix ??= $this->extractRouteSuffixFromRequest($request, $moduleSlug);

        $viewData = array_merge($additionalData, $dynamicTitleData);

        /** @var array<int, mixed>|null $statsParam */
        $statsParam = null;
        if (isset($additionalData['stats']) && is_array($additionalData['stats'])) {
            $statsParam = array_values($additionalData['stats']);
        }

        // PermissionChecker basado en entidad de dominio (permissions precalculados con caché cross-guard)
        $domainUser = null;
        if ($user instanceof AbstractDomainUser) {
            $domainUser = DomainUserMapper::toDomain($user);
        }

        $permissionChecker = function (string $permission) use ($domainUser, $user): bool {
            if (! $domainUser instanceof DomainUser) {
                return $this->permissionVerifier->checkCrossGuard($user, $permission);
            }

            if ($domainUser->hasPermission($permission)) {
                return true;
            }

            return $this->permissionVerifier->checkCrossGuard($user, $permission);
        };

        $viewContext = $this->viewComposer->composeModuleViewContext(
            moduleSlug: $moduleSlug,
            panelItemsConfig: $panelItemsConfig,
            contextualNavItemsConfig: $contextualNavItemsConfig,
            permissionChecker: $permissionChecker,
            user: $user instanceof \App\Interfaces\AuthenticatableUser ? $user : null,
            functionalName: $functionalName,
            data: $viewData,
            stats: $statsParam,
            routeSuffix: $routeSuffix,
            routeParams: $routeParams
        );

        $inertiaDir = $config->addon()->inertiaViewDirectory !== ''
          ? $config->addon()->inertiaViewDirectory
          : $moduleSlug;

        return $this->viewComposer->renderModuleView(
            view: $view,
            moduleViewPath: $inertiaDir,
            data: (array) $viewContext,
        );
    }

    /**
     * Resuelve la configuración del módulo desde el DTO o el array raw.
     *
     * @param  array<string, mixed>|null  $moduleConfig
     */
    private function resolveModuleConfig(
        string $moduleSlug,
        ?array $moduleConfig
    ): ModuleConfigInterface {
        if (is_array($moduleConfig) && $moduleConfig !== []) {
            // Legacy path: convert array to ModuleConfigInterface via registry
            // This only happens when controllers pass raw config arrays
            $dtoConfig = $this->addonRegistry->getModuleConfig($moduleSlug);
            if ($dtoConfig instanceof ModuleConfigInterface) {
                return $dtoConfig;
            }
        }

        $dtoConfig = $this->addonRegistry->getModuleConfig($moduleSlug);

        if (! $dtoConfig instanceof ModuleConfigInterface) {
            throw new RuntimeException(
                sprintf('No ModuleConfigInterface registered for slug "%s".', $moduleSlug)
            );
        }

        return $dtoConfig;
    }

    /**
     * Convierte panelItems del DTO a array de configuración para el builder.
     *
     * @return array<int, array<string, mixed>>
     */
    private function panelItemsToConfig(ModuleConfigInterface $config): array
    {
        $items = [];

        foreach ($config->panelItems() as $panelItem) {
            $items[] = [
                'name' => $panelItem->name,
                'description' => $panelItem->description,
                'route_name_suffix' => $panelItem->routeNameSuffix,
                'icon' => $panelItem->icon,
                'permission' => $panelItem->permission,
            ];
        }

        return $items;
    }

    /**
     * Resuelve la configuración de navegación contextual desde el DTO.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveContextualNavConfig(
        ModuleConfigInterface $config,
        string $moduleSlug,
        Request $request
    ): array {
        $contextualNav = $config->contextualNav();
        $suffix = $this->extractRouteSuffixFromRequest($request, $moduleSlug);

        $entries = $contextualNav->items[$suffix] ?? $contextualNav->items['default'] ?? [];

        $result = [];
        foreach ($entries as $entry) {
            if ($entry instanceof \Modules\Core\Domain\Menu\NavComponentLink) {
                $result[] = [
                    'title' => $entry->title,
                    'route_name_suffix' => $entry->routeNameSuffix,
                    'icon' => $entry->icon,
                    'permission' => $entry->permission,
                ];
            } elseif ($entry instanceof \Modules\Core\Domain\Menu\NavComponentGroup) {
                foreach ($entry->links as $link) {
                    $result[] = [
                        'title' => $link->title,
                        'route_name_suffix' => $link->routeNameSuffix,
                        'icon' => $link->icon,
                        'permission' => $link->permission,
                    ];
                }
            }
        }

        return $result;
    }

    private function extractRouteSuffixFromRequest(
        Request $request,
        string $moduleSlug
    ): string {
        $route = $request->route();
        $currentRoute = $route ? $route->getName() : null;

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.staff.%s.', $moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.staff.%s.', $moduleSlug))
            );
        }

        if (
            $currentRoute && str_starts_with(
                $currentRoute,
                sprintf('internal.%s.', $moduleSlug)
            )
        ) {
            return mb_substr(
                $currentRoute,
                mb_strlen(sprintf('internal.%s.', $moduleSlug))
            );
        }

        $parts = explode('.', $currentRoute ?? '');

        return end($parts) ?: 'panel';
    }
}
