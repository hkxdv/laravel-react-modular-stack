<?php

declare(strict_types=1);

use Modules\Core\Domain\Menu\BreadcrumbItem;
use Modules\Core\Domain\Menu\BreadcrumbMap;

uses(Tests\TestCase::class);

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

it('toArray maps each entry to its items toArray output', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Admin',
        routeNameSuffix: 'index',
    );

    $map = new BreadcrumbMap([
        'default' => [$crumb],
    ]);

    expect($map->toArray())->toBe([
        'default' => [
            [
                'title' => 'Admin',
                'route_name' => 'index',
            ],
        ],
    ]);
});
