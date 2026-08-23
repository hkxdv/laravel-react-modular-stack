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
