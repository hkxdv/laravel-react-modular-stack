<?php

declare(strict_types=1);

use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Examples\App\ModuleConfig\ExamplesModuleConfig;

it('contextualNav returns ContextualNavMap with example_panel link from config', function (): void {
    $adapter = new ExamplesModuleConfig();
    $result = $adapter->contextualNav();

    expect($result->items)->toHaveKey('default');

    $defaultItems = $result->items['default'];
    expect($defaultItems)->toHaveCount(1);
    assert($defaultItems[0] instanceof NavComponentLink);
    expect($defaultItems[0]->key)->toBe('example_panel')
        ->and($defaultItems[0]->title)->toBe('Panel de ejemplo')
        ->and($defaultItems[0]->routeNameSuffix)->toBe('index')
        ->and($defaultItems[0]->icon)->toBe('LayoutDashboard')
        ->and($defaultItems[0]->permission)->toBe('examples.dashboard.access');
});

it('panelItems returns list of PanelItem from config', function (): void {
    $adapter = new ExamplesModuleConfig();
    $result = $adapter->panelItems();

    expect($result)->not->toBeEmpty()
        ->and($result)->toHaveCount(1);

    $first = $result[0];
    expect($first->name)->toBe('Item de ejemplo 1')
        ->and($first->description)->toBe('Item de ejemplo 1 para la demostración del proyecto.')
        ->and($first->routeNameSuffix)->toBe('index')
        ->and($first->icon)->toBe('FilePlus2')
        ->and($first->permission)->toBe('examples.dashboard.access');
});

it('navItem returns NavItem with title from config', function (): void {
    $adapter = new ExamplesModuleConfig();
    $result = $adapter->navItem();

    assert($result instanceof Modules\Core\Domain\Menu\NavItem);
    expect($result->routeNameSuffix)->toBe('internal.tenant.examples.index')
        ->and($result->icon)->toBe('ClipboardList')
        ->and($result->showInNav)->toBeTrue();
});

it('breadcrumbs returns empty BreadcrumbMap (Examples has no breadcrumbs)', function (): void {
    $adapter = new ExamplesModuleConfig();
    $result = $adapter->breadcrumbs();

    expect($result->items)->toBeEmpty();
});

it('addon returns AddonConfig from config', function (): void {
    $adapter = new ExamplesModuleConfig();
    $result = $adapter->addon();

    expect($result->moduleSlug)->toBe('examples')
        ->and($result->functionalName)->toBe('Módulo de ejemplos (Tenant)');
});
