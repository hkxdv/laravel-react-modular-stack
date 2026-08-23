<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;

uses(Tests\TestCase::class);

it('creates a ContextualNavMap with items', function (): void {
    $link = new NavComponentLink(
        key: 'dashboard',
        title: 'Dashboard',
        routeNameSuffix: 'index',
        icon: 'LayoutDashboard',
    );

    $map = ContextualNavMap::of([
        'default' => [$link],
    ]);

    expect($map->items)->toHaveKey('default')
        ->and($map->items['default'])->toHaveCount(1);
});

it('of factory creates ContextualNavMap', function (): void {
    $map = ContextualNavMap::of([]);

    expect($map->items)->toBeEmpty();
});

// ── fromConfigArray ──

it('fromConfigArray resolves plain link keys', function (): void {
    $map = ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['panel', 'users_list']],
        linksArray: [
            'panel' => ['title' => 'Panel', 'route_name_suffix' => 'index', 'icon' => 'LayoutDashboard'],
            'users_list' => ['title' => 'Users', 'route_name_suffix' => 'users.index', 'icon' => 'Users'],
        ],
        groupsArray: [],
    );

    expect($map->items['default'])->toHaveCount(2);
    $defaultItems = $map->items['default'];
    assert($defaultItems[0] instanceof NavComponentLink);
    assert($defaultItems[1] instanceof NavComponentLink);
    expect($defaultItems[0]->key)->toBe('panel')
        ->and($defaultItems[1]->key)->toBe('users_list');
});

it('fromConfigArray resolves group: prefix to NavComponentGroup', function (): void {
    $map = ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['group:user_management']],
        linksArray: [
            'panel' => ['title' => 'Panel', 'route_name_suffix' => 'index', 'icon' => 'LayoutDashboard'],
            'users_list' => ['title' => 'Users', 'route_name_suffix' => 'users.index', 'icon' => 'Users'],
        ],
        groupsArray: [
            'user_management' => ['panel', 'users_list'],
        ],
    );

    expect($map->items['default'])->toHaveCount(1)
        ->and($map->items['default'][0])->toBeInstanceOf(NavComponentGroup::class);

    /** @var NavComponentGroup $group */
    $group = $map->items['default'][0];
    expect($group->name)->toBe('user_management')
        ->and($group->links)->toHaveCount(2);
});

it('fromConfigArray throws on missing link key', function (): void {
    ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['missing_link']],
        linksArray: [],
        groupsArray: [],
    );
})->throws(InvalidAddonConfig::class, "ContextualNavMap: link 'missing_link' not found in links config");

it('fromConfigArray throws on missing group key', function (): void {
    ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['group:missing_group']],
        linksArray: [],
        groupsArray: [],
    );
})->throws(InvalidAddonConfig::class, "ContextualNavMap: group 'missing_group' not found in groups config");

it('fromConfigArray throws on missing link inside group', function (): void {
    ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['group:my_group']],
        linksArray: [],
        groupsArray: [
            'my_group' => ['nonexistent_link'],
        ],
    );
})->throws(InvalidAddonConfig::class, "ContextualNavMap: link 'nonexistent_link' in group 'my_group' not found in links config");

it('fromConfigArray returns empty map for empty arrays', function (): void {
    $map = ContextualNavMap::fromConfigArray([], [], []);

    expect($map->items)->toBeEmpty();
});

it('fromConfigArray resolves mixed group and plain link references', function (): void {
    $map = ContextualNavMap::fromConfigArray(
        navArray: ['default' => ['group:nav_group', 'back_to_panel']],
        linksArray: [
            'back_to_panel' => ['title' => 'Back', 'route_name_suffix' => 'index', 'icon' => 'ArrowLeft'],
        ],
        groupsArray: [
            'nav_group' => ['back_to_panel'],
        ],
    );

    expect($map->items['default'])->toHaveCount(2)
        ->and($map->items['default'][0])->toBeInstanceOf(NavComponentGroup::class)
        ->and($map->items['default'][1])->toBeInstanceOf(NavComponentLink::class);
});
