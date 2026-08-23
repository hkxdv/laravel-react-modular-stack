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
