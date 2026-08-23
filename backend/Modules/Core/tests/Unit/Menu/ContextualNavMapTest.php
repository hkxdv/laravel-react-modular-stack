<?php

declare(strict_types=1);

use Modules\Core\Domain\Menu\ContextualNavMap;
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
