<?php

declare(strict_types=1);

use Modules\Core\Infrastructure\Laravel\Services\CorePermissionRegistry;
use PHPUnit\Framework\Assert;

use function Foundry\Helpers\configString;

// ── AUTC-S1 / D2: CorePermissionRegistry publishes to the default guard ──
// SECURITY: publishes to `config('auth.defaults.guard')` ONLY when no explicit
// guard is given; it must NOT publish to all `core.guards`.

it('emits system permissions with the default guard when guard is not set', function (): void {
    $registry = new CorePermissionRegistry();

    $permissions = $registry->permissions();

    $defaultGuard = configString('auth.defaults.guard', 'web');

    expect($permissions)->toHaveCount(2)
        ->and($permissions[0]['name'])->toBe('system.bypass')
        ->and($permissions[1]['name'])->toBe('permissions.sync');

    Assert::assertSame($defaultGuard, $permissions[0]['guard']);
    Assert::assertSame($defaultGuard, $permissions[1]['guard']);
});

it('emits exactly one guard per permission (no publish-to-all-guards)', function (): void {
    $registry = new CorePermissionRegistry();

    $permissions = $registry->permissions();

    foreach ($permissions as $permission) {
        Assert::assertSame(configString('auth.defaults.guard', 'web'), $permission['guard']);
    }
});

it('emits system permissions with the tenant guard when guard is tenant', function (): void {
    $registry = new CorePermissionRegistry('tenant');

    $permissions = $registry->permissions();

    expect($permissions)->toHaveCount(2);

    foreach ($permissions as $permission) {
        Assert::assertSame('tenant', $permission['guard']);
    }
});
