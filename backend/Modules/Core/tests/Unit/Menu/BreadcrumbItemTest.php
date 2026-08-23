<?php

declare(strict_types=1);

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbItem;

uses(Tests\TestCase::class);

it('creates a BreadcrumbItem with valid data', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Admin',
        routeNameSuffix: 'index',
    );

    expect($crumb->title)->toBe('Admin')
        ->and($crumb->routeNameSuffix)->toBe('index')
        ->and($crumb->dynamicTitleProp)->toBeNull();
});

it('creates a BreadcrumbItem with dynamicTitleProp', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Edit User',
        routeNameSuffix: 'users.edit',
        dynamicTitleProp: 'user.name',
    );

    expect($crumb->dynamicTitleProp)->toBe('user.name');
});

it('throws InvalidAddonConfig when title is empty', function (): void {
    new BreadcrumbItem(
        title: '',
        routeNameSuffix: 'index',
    );
})->throws(InvalidAddonConfig::class, 'BreadcrumbItem requires non-empty title');

// ── fromConfigArray ──

it('fromConfigArray creates BreadcrumbItem with valid data', function (): void {
    $crumb = BreadcrumbItem::fromConfigArray([
        'title' => 'Admin',
        'route_name_suffix' => 'index',
        'dynamic_title_prop' => 'admin.name',
    ]);

    expect($crumb->title)->toBe('Admin')
        ->and($crumb->routeNameSuffix)->toBe('index')
        ->and($crumb->dynamicTitleProp)->toBe('admin.name');
});

it('fromConfigArray creates BreadcrumbItem without dynamic_title_prop', function (): void {
    $crumb = BreadcrumbItem::fromConfigArray([
        'title' => 'Admin',
        'route_name_suffix' => 'index',
    ]);

    expect($crumb->dynamicTitleProp)->toBeNull();
});

it('fromConfigArray throws on missing title', function (): void {
    BreadcrumbItem::fromConfigArray([
        'route_name_suffix' => 'index',
    ]);
})->throws(InvalidAddonConfig::class, 'BreadcrumbItem fromConfigArray requires non-empty title');

it('fromConfigArray throws on empty title', function (): void {
    BreadcrumbItem::fromConfigArray([
        'title' => '',
        'route_name_suffix' => 'index',
    ]);
})->throws(InvalidAddonConfig::class, 'BreadcrumbItem fromConfigArray requires non-empty title');

it('fromConfigArray throws on missing route_name_suffix', function (): void {
    BreadcrumbItem::fromConfigArray([
        'title' => 'Admin',
    ]);
})->throws(InvalidAddonConfig::class, 'BreadcrumbItem fromConfigArray requires non-empty route_name_suffix');
