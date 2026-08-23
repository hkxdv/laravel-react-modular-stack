<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use App\Interfaces\AuthenticatableUser as User;
use Modules\Core\Contracts\AddonRegistryInterface;
use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Addon\AddonInstance;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavItem;
use Nwidart\Modules\Laravel\Module;

/**
 * Fake implementation of AddonRegistryInterface that returns configurable
 * data from a public `$configs` array keyed by module name.
 */
final class FakeAddonRegistry implements AddonRegistryInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $configs = [];

    /** @return array<Module> */
    public function getAvailableAddonsForUser(User $user): array
    {
        return [];
    }

    /** @return array<Module> */
    public function getAccessibleAddons(?User $user = null): array
    {
        return [];
    }

    /** @return array<Module> */
    public function getAllEnabledAddons(): array
    {
        return [];
    }

    public function getAddonConfig(string $moduleName): array
    {
        return $this->configs[$moduleName] ?? [];
    }

    public function getModuleConfig(string $slug): ?ModuleConfigInterface
    {
        $config = $this->configs[$slug] ?? null;
        if ($config === null) {
            return null;
        }

        $addon = AddonConfig::fromArray(ucfirst($slug), $config);

        $navItem = null;
        $navConfig = $config['nav_item'] ?? null;
        if (is_array($navConfig) && ($navConfig['show_in_nav'] ?? false) && ! empty($navConfig['route_name'])) {
            /** @var array<string, mixed> $navConfig */
            $routeName = is_string($navConfig['route_name']) ? $navConfig['route_name'] : '';
            $icon = is_string($navConfig['icon'] ?? null) ? $navConfig['icon'] : '';
            $showInMainNav = is_bool($navConfig['show_in_main_nav'] ?? null) && $navConfig['show_in_main_nav'];
            $basePermission = is_string($config['base_permission'] ?? null) ? $config['base_permission'] : null;
            $functionalName = is_string($config['functional_name'] ?? null) ? $config['functional_name'] : ucfirst($slug);

            $navItem = new NavItem(
                title: $functionalName,
                routeNameSuffix: $routeName,
                icon: $icon,
                permission: $basePermission,
                showInNav: true,
                showInMainNav: $showInMainNav,
            );
        }

        return new readonly class($addon, $navItem) implements ModuleConfigInterface
        {
            public function __construct(
                private AddonConfig $addon,
                private ?NavItem $navItem,
            ) {}

            public function addon(): AddonConfig
            {
                return $this->addon;
            }

            public function navItem(): ?NavItem
            {
                return $this->navItem;
            }

            public function contextualNav(): ContextualNavMap
            {
                return ContextualNavMap::of([]);
            }

            public function breadcrumbs(): BreadcrumbMap
            {
                return BreadcrumbMap::empty();
            }

            public function panelItems(): array
            {
                return [];
            }
        };
    }

    public function getAddonInstance(string $moduleName): ?AddonInstance
    {
        return null;
    }

    /** @return list<AddonInstance> */
    public function getAllEnabledAddonInstances(): array
    {
        return [];
    }

    public function clearConfigCache(): void
    {
        // No-op
    }

    /** @return array<int, array<string, mixed>> */
    public function getGlobalNavItems(?User $user = null): array
    {
        return [];
    }
}
