<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Core.
 *
 * Lee de config('core.*') y delega a factories DTO.
 */
final class CoreModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('core');

        return AddonConfig::fromArray('Core', $config);
    }

    public function navItem(): ?NavItem
    {
        return null;
    }

    public function contextualNav(): ContextualNavMap
    {
        /** @var array<string, list<string>> $navArray */
        $navArray = (array) config('core.contextual_nav', []);
        /** @var array<string, array<string, mixed>> $linksArray */
        $linksArray = (array) config('core.nav_components.links', []);
        /** @var array<string, list<string>> $groupsArray */
        $groupsArray = (array) config('core.nav_components.groups', []);

        return ContextualNavMap::fromConfigArray($navArray, $linksArray, $groupsArray);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        /** @var array<string, list<string>> $breadcrumbsArray */
        $breadcrumbsArray = (array) config('core.breadcrumbs', []);
        /** @var array<string, array<string, mixed>> $componentsArray */
        $componentsArray = (array) config('core.breadcrumb_components', []);

        return BreadcrumbMap::fromConfigArray($breadcrumbsArray, $componentsArray);
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        return [];
    }
}
