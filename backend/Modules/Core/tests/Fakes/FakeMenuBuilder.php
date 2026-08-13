<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use Modules\Core\Contracts\MenuBuilderInterface;
use Nwidart\Modules\Laravel\Module;

/**
 * Fake implementation of MenuBuilderInterface that tracks how many times
 * `assembleNavigationStructure` is called and returns a configurable result.
 *
 * Used by ViewComposerService tests to verify caching behavior.
 */
final class FakeMenuBuilder implements MenuBuilderInterface
{
    public int $assembleCount = 0;

    /** @var array<string, mixed> */
    public array $navigationResult = [
        'mainNavItems' => [],
        'moduleNavItems' => [],
        'contextualNavItems' => [],
        'globalNavItems' => [],
        'breadcrumbs' => [],
        'panelItems' => [],
    ];

    public function assembleNavigationStructure(
        callable $permissionChecker,
        ?string $moduleSlug = null,
        array $contextualItemsConfig = [],
        $user = null,
        ?string $functionalName = null,
        ?string $routeSuffix = null,
        array $routeParams = [],
        array $viewData = []
    ): array {
        $this->assembleCount++;

        return $this->navigationResult;
    }

    public function buildNavigation(
        string $navType,
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return [];
    }

    public function buildContextualNavItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return [];
    }

    public function buildPanelItems(
        array $itemsConfig,
        callable $permissionChecker,
        string $moduleSlug,
        ?string $functionalName = null
    ): array {
        return [];
    }

    /** @param  array<Module>  $modules */
    public function buildNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return [];
    }

    /** @param  array<Module>  $modules */
    public function buildModuleNavItems(
        array $modules,
        callable $permissionChecker
    ): array {
        return [];
    }

    /** @param  array<Module>  $allModules  @param  array<Module>  $accessibleModules */
    public function buildModuleCards(
        array $allModules,
        array $accessibleModules = []
    ): array {
        return [];
    }

    public function buildConfiguredBreadcrumbs(
        string $moduleSlug,
        string $routeSuffix,
        array $routeParams = [],
        array $viewData = []
    ): array {
        return [];
    }

    public function buildGlobalNavItems(
        array $itemsConfig,
        callable $permissionChecker
    ): array {
        return [];
    }

    public function resolveConfigReferences(
        $config,
        array $moduleConfig
    ): mixed {
        return $config;
    }

    public function isCurrentRoute(string $routeName): bool
    {
        return false;
    }
}
