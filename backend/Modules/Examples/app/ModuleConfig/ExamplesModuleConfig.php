<?php

declare(strict_types=1);

namespace Modules\Examples\App\ModuleConfig;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Examples.
 *
 * Lee de config('examples.*') y delega a factories DTO.
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

        return NavItem::fromConfigArray($nav);
    }

    public function contextualNav(): ContextualNavMap
    {
        /** @var array<string, list<string>> $navArray */
        $navArray = (array) config('examples.contextual_nav', []);
        /** @var array<string, array<string, mixed>> $linksArray */
        $linksArray = (array) config('examples.nav_components.links', []);
        /** @var array<string, list<string>> $groupsArray */
        $groupsArray = [];

        return ContextualNavMap::fromConfigArray($navArray, $linksArray, $groupsArray);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        return BreadcrumbMap::empty();
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = (array) config('examples.panel_items', []);

        return PanelItem::fromConfigArray($items);
    }
}
