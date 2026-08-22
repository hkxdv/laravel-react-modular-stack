<?php

declare(strict_types=1);

use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;

uses(Tests\TestCase::class);

it('creates a NavComponentGroup with valid data', function (): void {
    $link1 = new NavComponentLink(
        key: 'users_list',
        title: 'Users',
        routeNameSuffix: 'users.index',
        icon: 'Users',
    );
    $link2 = new NavComponentLink(
        key: 'users_create',
        title: 'Create User',
        routeNameSuffix: 'users.create',
        icon: 'UserPlus',
    );

    $group = new NavComponentGroup(
        name: 'user_management',
        links: [$link1, $link2],
    );

    expect($group->name)->toBe('user_management')
        ->and($group->links)->toHaveCount(2);
});

it('toArray maps each link to its toArray output', function (): void {
    $link = new NavComponentLink(
        key: 'users_list',
        title: 'Users',
        routeNameSuffix: 'users.index',
        icon: 'Users',
        permission: 'staff-users.view',
    );

    $group = new NavComponentGroup(
        name: 'user_management',
        links: [$link],
    );

    expect($group->toArray())->toBe([
        [
            'title' => 'Users',
            'route_name_suffix' => 'users.index',
            'icon' => 'Users',
            'permission' => 'staff-users.view',
        ],
    ]);
});
