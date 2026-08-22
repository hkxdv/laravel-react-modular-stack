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

it('preserves toArray key shape without dynamicTitleProp', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Admin',
        routeNameSuffix: 'index',
    );

    expect($crumb->toArray())->toBe([
        'title' => 'Admin',
        'route_name' => 'index',
    ]);
});

it('preserves toArray key shape with dynamicTitleProp', function (): void {
    $crumb = new BreadcrumbItem(
        title: 'Edit User',
        routeNameSuffix: 'users.edit',
        dynamicTitleProp: 'user.name',
    );

    expect($crumb->toArray())->toBe([
        'title' => 'Edit User',
        'route_name' => 'users.edit',
        'dynamic_title_prop' => 'user.name',
    ]);
});
