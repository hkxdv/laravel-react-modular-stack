<?php

declare(strict_types=1);

use Modules\Core\Infrastructure\Laravel\Services\CorePermissionRegistry;
use PHPUnit\Framework\Assert;

// ── CorePermissionRegistry: default guard is the backoffice ('staff') ──
// SECURITY: the default is pinned to the administrative guard, NOT to
// config('auth.defaults.guard') (the app default is 'web' and would register
// system permissions for the wrong guard), and never to all `core.guards`.
// A module can pass an explicit guard via the constructor (testable capability).

it('emits system permissions with the backoffice guard by default', function (): void {
    $registry = new CorePermissionRegistry();

    $permissions = $registry->permissions();

    expect($permissions)->toHaveCount(2)
        ->and($permissions[0]['name'])->toBe('system.bypass')
        ->and($permissions[1]['name'])->toBe('permissions.sync');

    Assert::assertSame('staff', $permissions[0]['guard']);
    Assert::assertSame('staff', $permissions[1]['guard']);
});

it('emits exactly one guard per permission (no publish-to-all-guards)', function (): void {
    $registry = new CorePermissionRegistry();

    $permissions = $registry->permissions();

    foreach ($permissions as $permission) {
        Assert::assertSame('staff', $permission['guard']);
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
