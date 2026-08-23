<?php

declare(strict_types=1);

namespace Modules\Admin\App\ModuleConfig;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Admin.
 *
 * Lee de config('admin.*') y delega a factories DTO.
 */
final class AdminModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('admin');

        return AddonConfig::fromArray('Admin', $config);
    }

    public function navItem(): ?NavItem
    {
        /** @var array<string, mixed>|null $nav */
        $nav = config('admin.nav_item');

        if (! is_array($nav)) {
            return null;
        }

        /** @var array<string, mixed> $config */
        $config = (array) config('admin');

        $rawFunctionalName = $config['functional_name'] ?? '';
        $fallbackTitle = is_string($rawFunctionalName) ? $rawFunctionalName : '';

        return NavItem::fromConfigArray($nav, $fallbackTitle);
    }

    public function contextualNav(): ContextualNavMap
    {
        /** @var array<string, list<string>> $navArray */
        $navArray = (array) config('admin.contextual_nav', []);
        /** @var array<string, array<string, mixed>> $linksArray */
        $linksArray = (array) config('admin.nav_components.links', []);
        /** @var array<string, list<string>> $groupsArray */
        $groupsArray = (array) config('admin.nav_components.groups', []);

        return ContextualNavMap::fromConfigArray($navArray, $linksArray, $groupsArray);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        /** @var array<string, list<string>> $breadcrumbsArray */
        $breadcrumbsArray = (array) config('admin.breadcrumbs', []);
        /** @var array<string, array<string, mixed>> $componentsArray */
        $componentsArray = (array) config('admin.breadcrumb_components', []);

        return BreadcrumbMap::fromConfigArray($breadcrumbsArray, $componentsArray);
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = (array) config('admin.panel_items', []);

        return PanelItem::fromConfigArray($items);
    }
}
