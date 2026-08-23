<?php

declare(strict_types=1);

namespace Modules\Examples\App\ModuleConfig;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Examples.
 */
final class ExamplesModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('examples');

        return AddonConfig::fromArray('Examples', $config);
    }

    public function navItem(): ?NavItem
    {
        /** @var array<string, mixed>|null $nav */
        $nav = config('examples.nav_item');

        if (! is_array($nav)) {
            return null;
        }

        $title = $nav['title'] ?? '';
        $routeName = $nav['route_name'] ?? '';
        $icon = $nav['icon'] ?? '';
        $permission = $nav['permission'] ?? null;
        $showInNav = $nav['show_in_nav'] ?? true;
        $showInMainNav = $nav['show_in_main_nav'] ?? false;

        return new NavItem(
            title: is_string($title) ? $title : '',
            routeNameSuffix: is_string($routeName) ? $routeName : '',
            icon: is_string($icon) ? $icon : '',
            permission: is_string($permission) ? $permission : null,
            showInNav: is_bool($showInNav) ? $showInNav : true,
            showInMainNav: is_bool($showInMainNav) && $showInMainNav,
        );
    }

    public function contextualNav(): ContextualNavMap
    {
        /** @var array<string, array<int, mixed>> $contextualNav */
        $contextualNav = (array) config('examples.contextual_nav', []);

        /** @var array<string, list<NavComponentLink>> $items */
        $items = [];
        foreach ($contextualNav as $suffix => $entries) {
            $items[$suffix] = $this->buildNavLinks($entries);
        }

        return ContextualNavMap::of($items);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        return BreadcrumbMap::empty();
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = (array) config('examples.panel_items', []);

        $result = [];
        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            $description = $item['description'] ?? '';
            $routeNameSuffix = $item['route_name_suffix'] ?? '';
            $icon = $item['icon'] ?? '';
            $permission = $item['permission'] ?? null;

            if (is_string($name) && is_string($routeNameSuffix) && $name !== '' && $routeNameSuffix !== '') {
                $result[] = new PanelItem(
                    name: $name,
                    description: is_string($description) ? $description : '',
                    routeNameSuffix: $routeNameSuffix,
                    icon: is_string($icon) ? $icon : '',
                    permission: is_string($permission) ? $permission : null,
                );
            }
        }

        return $result;
    }

    /**
     * Construye NavComponentLink items desde arrays inline.
     *
     * @param  array<int, mixed>  $entries
     * @return list<NavComponentLink>
     */
    private function buildNavLinks(array $entries): array
    {
        $links = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (! isset($entry['title'])) {
                continue;
            }

            /** @var array<string, mixed> $entry */
            $title = $entry['title'];
            $icon = $entry['icon'] ?? '';
            $permission = $entry['permission'] ?? null;
            $routeNameSuffix = $entry['route_name_suffix'] ?? $entry['route_name'] ?? '';

            if (is_string($title) && is_string($routeNameSuffix) && $routeNameSuffix !== '') {
                $links[] = new NavComponentLink(
                    key: $routeNameSuffix,
                    title: $title,
                    routeNameSuffix: $routeNameSuffix,
                    icon: is_string($icon) ? $icon : '',
                    permission: is_string($permission) ? $permission : null,
                );
            }
        }

        return $links;
    }
}
