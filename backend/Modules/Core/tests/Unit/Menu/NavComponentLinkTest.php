<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\NavComponentLink;

uses(Tests\TestCase::class);

it('creates a NavComponentLink with valid data', function (): void {
    $link = new NavComponentLink(
        key: 'users_list',
        title: 'Users',
        routeNameSuffix: 'users.index',
        icon: 'Users',
        permission: 'staff-users.view',
    );

    expect($link->key)->toBe('users_list')
        ->and($link->title)->toBe('Users')
        ->and($link->routeNameSuffix)->toBe('users.index')
        ->and($link->icon)->toBe('Users')
        ->and($link->permission)->toBe('staff-users.view');
});

it('creates a NavComponentLink with null permission', function (): void {
    $link = new NavComponentLink(
        key: 'dashboard',
        title: 'Dashboard',
        routeNameSuffix: 'index',
        icon: 'LayoutDashboard',
    );

    expect($link->permission)->toBeNull();
});

it('throws InvalidAddonConfig when routeNameSuffix is empty', function (): void {
    new NavComponentLink(
        key: 'users_list',
        title: 'Users',
        routeNameSuffix: '',
        icon: 'Users',
    );
})->throws(InvalidAddonConfig::class, 'NavComponentLink requires non-empty routeNameSuffix');
