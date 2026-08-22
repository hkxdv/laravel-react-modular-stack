<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;

/**
 * Configuración declarativa del módulo Core.
 */
final class CoreModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('core');

        return AddonConfig::fromArray('Core', $config);
    }

    public function navItem(): ?array
    {
        return null;
    }

    /** @return array<string, array<int, mixed>> */
    public function contextualNav(): array
    {
        /** @var array<string, array<int, mixed>> $nav */
        $nav = (array) config('core.contextual_nav', []);

        return $nav;
    }

    /** @return array<string, array<int, mixed>> */
    public function breadcrumbs(): array
    {
        /** @var array<string, array<int, mixed>> $crumbs */
        $crumbs = (array) config('core.breadcrumbs', []);

        return $crumbs;
    }

    /** @return list<array{name: string, description: string, route_name_suffix: string, icon: string, permission: string|null}> */
    public function panelItems(): array
    {
        return [];
    }
}
