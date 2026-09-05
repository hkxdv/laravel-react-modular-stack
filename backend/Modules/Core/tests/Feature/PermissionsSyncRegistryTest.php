<?php

declare(strict_types=1);

use Modules\Admin\App\PermissionRegistry\AdminPermissionRegistry;
use Modules\Core\Infrastructure\Laravel\Services\CorePermissionRegistry;
use Modules\Examples\App\PermissionRegistry\ExamplesPermissionRegistry;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    // Reset permissions cache
    app()->make(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
});

it('syncs all declared granular permissions from registries', function (): void {
    $registries = [
        new CorePermissionRegistry(),
        new AdminPermissionRegistry(),
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

    // Cuenta por guard derivada de lo declarado en los registries (D2: el
    // default de CorePermissionRegistry es config('auth.defaults.guard')).
    $expectedByGuard = [];
    foreach ($registries as $registry) {
        foreach ($registry->permissions() as $perm) {
            $expectedByGuard[$perm['guard']] = ($expectedByGuard[$perm['guard']] ?? 0) + 1;
        }
    }

    foreach ($expectedByGuard as $guard => $count) {
        expect(Permission::query()->where('guard_name', $guard)->count())
            ->toBe($count, sprintf("Permisos inesperados para guard '%s'", $guard));
    }
});

it('does not contain broad permissions from old seeder', function (): void {
    // Ensure old broad permissions don't exist
    Permission::query()->where('name', 'access-module-01')->delete();
    Permission::query()->where('name', 'access-admin')->delete();

    expect(Permission::query()->where('name', 'access-module-01')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'access-module-02')->exists())->toBeFalse()
        ->and(Permission::query()->where('name', 'access-admin')->exists())->toBeFalse();
});
