<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Application\View\AuthPageProps;
use Modules\Core\Application\View\ComposeInertiaProps;
use Modules\Core\Domain\User\DTO\UserDto;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;
use ReflectionProperty;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ── AUTC-S3: AuthPageProps.user se amplía a UserDto|null ──

it('auth props user property type is widened to UserDto|null (AUTC-S3)', function (): void {
    $type = (new ReflectionProperty(AuthPageProps::class, 'user'))->getType();

    expect((string) $type)->toBe('?Modules\Core\Domain\User\DTO\UserDto');
});

it('auth props user is an instance of UserDto for staff (widened contract)', function (): void {
    $staffRole = Role::query()->create(['name' => 'staff', 'guard_name' => 'staff']);
    $user = StaffUsersFactory::new()->create();
    $user->assignRole($staffRole);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'staff' ? $user : null;
    });

    $props = app(ComposeInertiaProps::class)->execute($request);

    expect($props->auth->user)->toBeInstanceOf(UserDto::class)
        ->and($props->auth->user->user_type)->toBe('staff');
});

it('staff guard produces auth props with staff user_type', function (): void {
    $staffRole = Role::query()->create(['name' => 'staff', 'guard_name' => 'staff']);
    $user = StaffUsersFactory::new()->create();
    $user->assignRole($staffRole);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'staff' ? $user : null;
    });

    $composer = app(ComposeInertiaProps::class);
    $props = $composer->execute($request);

    expect($props->auth->user)->not->toBeNull()
        ->and($props->auth->user->user_type)->toBe('staff');
});

it('tenant guard produces auth props with tenant user_type', function (): void {
    $user = ExampleTenantUserFactory::new()->create(['name' => 'Tenant Test', 'email' => 'tenant@test.com']);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'tenant' ? $user : null;
    });

    $composer = app(ComposeInertiaProps::class);
    $props = $composer->execute($request);

    expect($props->auth->user)->not->toBeNull()
        ->and($props->auth->user->user_type)->toBe('tenant');
});

it('no user produces guest auth props with null user', function (): void {
    $request = Request::create('/test', 'GET');

    $composer = app(ComposeInertiaProps::class);
    $props = $composer->execute($request);

    expect($props->auth->user)->toBeNull()
        ->and($props->auth->impersonate)->toBe(false);
});

// ── DECOUPLE-SURPRISE: composeSecurityProps reads per-guard 2FA config ──

it('security props twofact required follow per-guard core guards config', function (): void {
    config(['core.guards.staff.two_factor_required' => true]);
    config(['core.guards.tenant.two_factor_required' => false]);

    $staffRole = Role::query()->create(['name' => 'staff', 'guard_name' => 'staff']);
    $staff = StaffUsersFactory::new()->create();
    $staff->assignRole($staffRole);

    $staffRequest = Request::create('/test', 'GET');
    $staffRequest->setLaravelSession(app('session')->driver());
    $staffRequest->setUserResolver(function ($guard = null) use ($staff) {
        return $guard === 'staff' ? $staff : null;
    });

    $staffProps = app(ComposeInertiaProps::class)->execute($staffRequest);

    expect($staffProps->security->twoFactorRequired)->toBeTrue();

    $tenant = ExampleTenantUserFactory::new()->create(['name' => 'Tenant 2FA', 'email' => 'tenant-2fa@test.com']);

    $tenantRequest = Request::create('/test', 'GET');
    $tenantRequest->setLaravelSession(app('session')->driver());
    $tenantRequest->setUserResolver(function ($guard = null) use ($tenant) {
        return $guard === 'tenant' ? $tenant : null;
    });

    $tenantProps = app(ComposeInertiaProps::class)->execute($tenantRequest);

    expect($tenantProps->security->twoFactorRequired)->toBeFalse()
        ->and($tenantProps->security->twoFactorEnabled)->toBeFalse()
        ->and($tenantProps->security->twoFactorPending)->toBeFalse();
});

it('security props fall back to legacy staff 2fa config when per-guard key is absent', function (): void {
    config(['core.guards.staff' => [
        'login_route' => 'login',
        'redirect_route' => 'login',
        'provider' => 'staff',
    ]]);
    config(['security.two_factor.staff.required' => true]);

    $staffRole = Role::query()->create(['name' => 'staff', 'guard_name' => 'staff']);
    $staff = StaffUsersFactory::new()->create();
    $staff->assignRole($staffRole);

    $request = Request::create('/test', 'GET');
    $request->setLaravelSession(app('session')->driver());
    $request->setUserResolver(function ($guard = null) use ($staff) {
        return $guard === 'staff' ? $staff : null;
    });

    $props = app(ComposeInertiaProps::class)->execute($request);

    expect($props->security->twoFactorRequired)->toBeTrue();
});

it('security props are all false for guests without a domain user', function (): void {
    config(['core.guards.staff.two_factor_required' => true]);

    $request = Request::create('/test', 'GET');

    $props = app(ComposeInertiaProps::class)->execute($request);

    expect($props->security->twoFactorRequired)->toBeFalse()
        ->and($props->security->twoFactorEnabled)->toBeFalse()
        ->and($props->security->twoFactorPending)->toBeFalse();
});
