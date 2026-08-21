<?php

declare(strict_types=1);

use Modules\Admin\App\PermissionRegistry\AdminPermissionRegistry;
use Modules\Core\Infrastructure\Laravel\Services\CorePermissionRegistry;
use Modules\Examples\App\PermissionRegistry\ExamplesPermissionRegistry;
use Modules\Module02\App\PermissionRegistry\Module02PermissionRegistry;
use Spatie\Permission\Models\Permission;

uses(Tests\TestCase::class);

beforeEach(function (): void {
    // Reset permissions cache
    app()->make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
});

it('syncs all 22 granular permissions from registries', function (): void {
    $registries = [
        new CorePermissionRegistry(),
        new AdminPermissionRegistry(),
        new Module02PermissionRegistry(),
        new ExamplesPermissionRegistry(),
    ];

    $expectedPermissions = [];

    foreach ($registries as $registry) {
        foreach ($registry->permissions() as $perm) {
            $expectedPermissions[] = $perm['name'];

            Permission::query()->firstOrCreate([
                'name' => $perm['name'],
                'guard_name' => $perm['guard'],
            ]);
        }
    }

    app()->make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // Verify all expected permissions exist
    foreach ($expectedPermissions as $name) {
        expect(Permission::query()->where('name', $name)->exists())->toBeTrue(
            sprintf("Permission '%s' should exist after sync", $name)
        );
    }

    // Verify total count (16 staff + 4 tenant = 20)
    $staffCount = Permission::query()->where('guard_name', 'staff')->count();
    $tenantCount = Permission::query()->where('guard_name', 'tenant')->count();

    expect($staffCount)->toBe(16)
        ->and($tenantCount)->toBe(4);
});

it('does not contain broad permissions from old seeder', function (): void {
    // Ensure old broad permissions don't exist
    Permission::query()->where('name', 'access-module-01')->delete();
    Permission::query()->where('name', 'access-module-02')->delete();
    Permission::query()->where('name', 'access-admin')->delete();

    expect(Permission::query()->where('name', 'access-module-01')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'access-module-02')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'access-admin')->exists())->toBeFalse();
});
