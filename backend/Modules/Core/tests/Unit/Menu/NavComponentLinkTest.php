<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\NavComponentLink;

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

// ── fromConfigArray ──

it('fromConfigArray creates NavComponentLink with valid data', function (): void {
    $link = NavComponentLink::fromConfigArray('panel', [
        'title' => 'Panel',
        'route_name_suffix' => 'index',
        'icon' => 'LayoutDashboard',
        'permission' => 'rbac.view',
    ]);

    expect($link->key)->toBe('panel')
        ->and($link->title)->toBe('Panel')
        ->and($link->routeNameSuffix)->toBe('index')
        ->and($link->icon)->toBe('LayoutDashboard')
        ->and($link->permission)->toBe('rbac.view');
});

it('fromConfigArray creates NavComponentLink without permission', function (): void {
    $link = NavComponentLink::fromConfigArray('dashboard', [
        'title' => 'Dashboard',
        'route_name_suffix' => 'index',
        'icon' => 'LayoutDashboard',
    ]);

    expect($link->permission)->toBeNull();
});

it('fromConfigArray throws on missing title', function (): void {
    NavComponentLink::fromConfigArray('x', [
        'route_name_suffix' => 'index',
        'icon' => 'LayoutDashboard',
    ]);
})->throws(InvalidAddonConfig::class, "NavComponentLink 'x' requires non-empty title");

it('fromConfigArray throws on missing route_name_suffix', function (): void {
    NavComponentLink::fromConfigArray('x', [
        'title' => 'X',
        'icon' => 'LayoutDashboard',
    ]);
})->throws(InvalidAddonConfig::class, "NavComponentLink 'x' requires non-empty route_name_suffix");

it('fromConfigArray throws on empty route_name_suffix', function (): void {
    NavComponentLink::fromConfigArray('x', [
        'title' => 'X',
        'route_name_suffix' => '',
        'icon' => 'LayoutDashboard',
    ]);
})->throws(InvalidAddonConfig::class, "NavComponentLink 'x' requires non-empty route_name_suffix");

it('fromConfigArray throws on missing icon', function (): void {
    NavComponentLink::fromConfigArray('x', [
        'title' => 'X',
        'route_name_suffix' => 'index',
    ]);
})->throws(InvalidAddonConfig::class, "NavComponentLink 'x' requires non-empty icon");
