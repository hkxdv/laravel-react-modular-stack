<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbItem;
use Modules\Core\Domain\Menu\BreadcrumbMap;

it('creates a BreadcrumbMap with items', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Admin',
        routeNameSuffix: 'index',
    );

    $map = new BreadcrumbMap([
        'default' => [$crumb],
    ]);

    expect($map->items)->toHaveKey('default')
        ->and($map->items['default'])->toHaveCount(1);
});

it('empty factory creates empty BreadcrumbMap', function (): void {
    $map = BreadcrumbMap::empty();

    expect($map->items)->toBeEmpty();
});

// ── fromConfigArray ──

it('fromConfigArray resolves component keys', function (): void {
    $map = BreadcrumbMap::fromConfigArray(
        breadcrumbsArray: [
            'default' => ['admin_root'],
            'users.index' => ['admin_root', 'users_list'],
        ],
        componentsArray: [
            'admin_root' => ['title' => 'Admin', 'route_name_suffix' => 'index'],
            'users_list' => ['title' => 'Users', 'route_name_suffix' => 'users.index'],
        ],
    );

    expect($map->items['default'])->toHaveCount(1)
        ->and($map->items['users.index'])->toHaveCount(2)
        ->and($map->items['default'][0]->title)->toBe('Admin')
        ->and($map->items['users.index'][1]->title)->toBe('Users');
});

it('fromConfigArray throws on missing component key', function (): void {
    BreadcrumbMap::fromConfigArray(
        breadcrumbsArray: ['default' => ['missing_key']],
        componentsArray: [],
    );
})->throws(InvalidAddonConfig::class, "BreadcrumbMap: component 'missing_key' not found in breadcrumb_components config");

it('fromConfigArray returns empty map for empty arrays', function (): void {
    $map = BreadcrumbMap::fromConfigArray([], []);

    expect($map->items)->toBeEmpty();
});

it('fromConfigArray preserves dynamic_title_prop', function (): void {
    $map = BreadcrumbMap::fromConfigArray(
        breadcrumbsArray: ['users.edit' => ['users_edit']],
        componentsArray: [
            'users_edit' => [
                'title' => 'Edit User',
                'route_name_suffix' => 'users.edit',
                'dynamic_title_prop' => 'user.name',
            ],
        ],
    );

    expect($map->items['users.edit'][0]->dynamicTitleProp)->toBe('user.name');
});
