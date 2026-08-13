<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Infrastructure\Laravel\Listeners\CacheUserPermissionsListener;
use Modules\Core\Infrastructure\Laravel\Services\PermissionService;

use function Foundry\Helpers\cacheInt;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array']);
});

// ── CT-LISTENER-01: increments perm_version when handling permission change event ──

it('increments user perm_version when handling permission change event', function (): void {
    // Seed perm_version = 3
    Cache::store('array')->put('user.42.perm_version', 3, now()->addDays(30));

    // Mock Authenticatable user with getAuthIdentifier() → 42
    $user = Mockery::mock(Authenticatable::class);
    /** @phpstan-ignore method.notFound */
    $user->shouldReceive('getAuthIdentifier')->andReturn(42);
    // forgetCachedPermissions is NOT on Authenticatable interface — method_exists returns false, skipped

    // Create event stub with ->user property
    $event = new stdClass();
    $event->user = $user;

    $listener = new CacheUserPermissionsListener(new PermissionService());
    $listener->handle($event);

    expect(cacheInt('user.42.perm_version', 0))->toBe(4);
});
