<?php

declare(strict_types=1);

namespace Modules\Examples\App\ModuleConfig;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;

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

    public function navItem(): ?array
    {
        $nav = config('examples.nav_item');

        /** @var array<string, mixed>|null $nav */
        return is_array($nav) ? $nav : null;
    }

    /** @return array<string, array<int, mixed>> */
    public function contextualNav(): array
    {
        /** @var array<string, array<int, mixed>> $nav */
        $nav = (array) config('examples.contextual_nav', []);

        return $nav;
    }

    /** @return array<string, array<int, mixed>> */
    public function breadcrumbs(): array
    {
        return [];
    }

    /** @return list<array{name: string, description: string, route_name_suffix: string, icon: string, permission: string|null}> */
    public function panelItems(): array
    {
        /** @var list<array{name: string, description: string, route_name_suffix: string, icon: string, permission: string|null}> $items */
        $items = (array) config('examples.panel_items', []);

        return $items;
    }
}
