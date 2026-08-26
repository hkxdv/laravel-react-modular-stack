<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\NavItem;

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

// ── fromConfigArray ──

it('fromConfigArray creates NavItem with valid data', function (): void {
    $nav = NavItem::fromConfigArray([
        'route_name' => 'internal.staff.admin.index',
        'title' => 'Admin',
        'icon' => 'ShieldCheck',
        'permission' => 'rbac.view',
        'show_in_nav' => true,
        'show_in_main_nav' => false,
    ]);

    expect($nav->title)->toBe('Admin')
        ->and($nav->routeNameSuffix)->toBe('internal.staff.admin.index')
        ->and($nav->icon)->toBe('ShieldCheck')
        ->and($nav->permission)->toBe('rbac.view')
        ->and($nav->showInNav)->toBeTrue()
        ->and($nav->showInMainNav)->toBeFalse();
});

it('fromConfigArray uses fallbackTitle when title is empty', function (): void {
    $nav = NavItem::fromConfigArray(
        ['route_name' => 'index', 'icon' => 'ShieldCheck'],
        fallbackTitle: 'Módulo de Administración',
    );

    expect($nav->title)->toBe('Módulo de Administración');
});

it('fromConfigArray prefers title over fallbackTitle', function (): void {
    $nav = NavItem::fromConfigArray(
        ['route_name' => 'index', 'title' => 'Custom', 'icon' => 'ShieldCheck'],
        fallbackTitle: 'Fallback',
    );

    expect($nav->title)->toBe('Custom');
});

it('fromConfigArray throws on missing route_name', function (): void {
    NavItem::fromConfigArray(['icon' => 'ShieldCheck']);
})->throws(InvalidAddonConfig::class, 'NavItem fromConfigArray requires non-empty route_name');

it('fromConfigArray throws on empty route_name', function (): void {
    NavItem::fromConfigArray(['route_name' => '', 'icon' => 'ShieldCheck']);
})->throws(InvalidAddonConfig::class, 'NavItem fromConfigArray requires non-empty route_name');
