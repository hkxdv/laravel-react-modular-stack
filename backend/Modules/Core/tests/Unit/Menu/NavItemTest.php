<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\NavItem;

uses(Tests\TestCase::class);

it('creates a NavItem with valid data', function (): void {
    $nav = new NavItem(
        title: 'Admin',
        routeNameSuffix: 'internal.staff.admin.index',
        icon: 'ShieldCheck',
        permission: 'rbac.view',
        showInNav: true,
    );

    expect($nav->title)->toBe('Admin')
        ->and($nav->routeNameSuffix)->toBe('internal.staff.admin.index')
        ->and($nav->icon)->toBe('ShieldCheck')
        ->and($nav->permission)->toBe('rbac.view')
        ->and($nav->showInNav)->toBeTrue();
});

it('creates a NavItem with default values', function (): void {
    $nav = new NavItem(
        title: 'Admin',
        routeNameSuffix: 'index',
        icon: 'ShieldCheck',
    );

    expect($nav->permission)->toBeNull()
        ->and($nav->showInNav)->toBeTrue();
});

it('throws InvalidAddonConfig when routeNameSuffix is empty', function (): void {
    new NavItem(
        title: 'Admin',
        routeNameSuffix: '',
        icon: 'ShieldCheck',
    );
})->throws(InvalidAddonConfig::class, 'NavItem requires non-empty routeNameSuffix');

it('preserves toArray key shape', function (): void {
    $nav = new NavItem(
        title: 'Admin',
        routeNameSuffix: 'internal.staff.admin.index',
        icon: 'ShieldCheck',
        permission: 'rbac.view',
        showInNav: true,
    );

    expect($nav->toArray())->toBe([
        'show_in_nav' => true,
        'route_name' => 'internal.staff.admin.index',
        'icon' => 'ShieldCheck',
        'permission' => 'rbac.view',
    ]);
});

it('show factory creates NavItem with showInNav true', function (): void {
    $nav = NavItem::show(
        routeName: 'internal.staff.admin.index',
        icon: 'ShieldCheck',
        permission: 'rbac.view',
    );

    expect($nav->showInNav)->toBeTrue()
        ->and($nav->routeNameSuffix)->toBe('internal.staff.admin.index')
        ->and($nav->icon)->toBe('ShieldCheck')
        ->and($nav->permission)->toBe('rbac.view');
});
