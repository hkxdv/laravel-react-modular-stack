<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Panel\PanelItem;

uses(Tests\TestCase::class);

it('creates a PanelItem with valid data', function (): void {
    $panel = new PanelItem(
        name: 'Users',
        description: 'Manage users',
        routeNameSuffix: 'users.index',
        icon: 'Users',
        permission: 'staff-users.view',
    );

    expect($panel->name)->toBe('Users')
        ->and($panel->description)->toBe('Manage users')
        ->and($panel->routeNameSuffix)->toBe('users.index')
        ->and($panel->icon)->toBe('Users')
        ->and($panel->permission)->toBe('staff-users.view');
});

it('creates a PanelItem with null permission', function (): void {
    $panel = new PanelItem(
        name: 'Dashboard',
        description: 'Main dashboard',
        routeNameSuffix: 'index',
        icon: 'LayoutDashboard',
    );

    expect($panel->permission)->toBeNull();
});

it('throws InvalidAddonConfig when name is empty', function (): void {
    new PanelItem(
        name: '',
        description: 'Manage users',
        routeNameSuffix: 'users.index',
        icon: 'Users',
    );
})->throws(InvalidAddonConfig::class, 'PanelItem requires non-empty name');

it('throws InvalidAddonConfig when routeNameSuffix is empty', function (): void {
    new PanelItem(
        name: 'Users',
        description: 'Manage users',
        routeNameSuffix: '',
        icon: 'Users',
    );
})->throws(InvalidAddonConfig::class, 'PanelItem requires non-empty routeNameSuffix');

// ── fromConfigArray ──

it('fromConfigArray creates PanelItem list with valid data', function (): void {
    $panels = PanelItem::fromConfigArray([
        [
            'name' => 'Users',
            'description' => 'Manage users',
            'route_name_suffix' => 'users.index',
            'icon' => 'Users',
            'permission' => 'staff-users.view',
        ],
    ]);

    expect($panels)->toHaveCount(1)
        ->and($panels[0]->name)->toBe('Users')
        ->and($panels[0]->description)->toBe('Manage users')
        ->and($panels[0]->routeNameSuffix)->toBe('users.index')
        ->and($panels[0]->icon)->toBe('Users')
        ->and($panels[0]->permission)->toBe('staff-users.view');
});

it('fromConfigArray creates PanelItem with minimal data', function (): void {
    $panels = PanelItem::fromConfigArray([
        [
            'name' => 'Dashboard',
            'route_name_suffix' => 'index',
        ],
    ]);

    expect($panels[0]->description)->toBe('')
        ->and($panels[0]->icon)->toBe('')
        ->and($panels[0]->permission)->toBeNull();
});

it('fromConfigArray throws on missing name', function (): void {
    PanelItem::fromConfigArray([
        ['route_name_suffix' => 'index'],
    ]);
})->throws(InvalidAddonConfig::class, 'PanelItem [0] requires non-empty name');

it('fromConfigArray throws on empty name', function (): void {
    PanelItem::fromConfigArray([
        ['name' => '', 'route_name_suffix' => 'index'],
    ]);
})->throws(InvalidAddonConfig::class, 'PanelItem [0] requires non-empty name');

it('fromConfigArray throws on missing route_name_suffix', function (): void {
    PanelItem::fromConfigArray([
        ['name' => 'Users'],
    ]);
})->throws(InvalidAddonConfig::class, 'PanelItem [0] requires non-empty route_name_suffix');

it('fromConfigArray throws on empty route_name_suffix', function (): void {
    PanelItem::fromConfigArray([
        ['name' => 'Users', 'route_name_suffix' => ''],
    ]);
})->throws(InvalidAddonConfig::class, 'PanelItem [0] requires non-empty route_name_suffix');
