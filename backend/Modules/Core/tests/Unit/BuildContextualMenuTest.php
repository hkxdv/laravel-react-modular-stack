<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Core\Application\Menu\BuildContextualMenu;
use Modules\Core\Infrastructure\Laravel\Events\MenuPermissionDenied;
use Modules\Core\Tests\Fakes\FakePermissionChecker;

beforeEach(function (): void {
    config(['cache.default' => 'array']);
});

// ── CT-MENU-01: permission denial increments counter and dispatches event ──

it('increments denial counter and dispatches MenuPermissionDenied when permission is denied', function (): void {
    Event::fake([MenuPermissionDenied::class]);

    $builder = new BuildContextualMenu();

    $itemsConfig = [
        [
            'title' => 'Settings',
            'href' => '/settings',
            'permission' => 'admin.settings',
        ],
    ];

    $builder->execute(
        navType: 'contextual',
        itemsConfig: $itemsConfig,
        permissionChecker: FakePermissionChecker::deny('admin.settings'),
        moduleSlug: 'core',
    );

    // Cache::increment was called for denial metrics
    expect(Cache::get('metrics:navigation:denied:total'))->toBe(1);

    // Event was dispatched
    Event::assertDispatched(fn (MenuPermissionDenied $event): bool => $event->permission === 'admin.settings'
        && $event->moduleSlug === 'core'
        && $event->context === 'contextual_nav');
});
