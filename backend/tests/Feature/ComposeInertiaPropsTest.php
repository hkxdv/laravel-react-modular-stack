<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Application\View\ComposeInertiaProps;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

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

    expect($props->auth->staff)->not->toBeNull()
        ->and($props->auth->staff['user_type'])->toBe('staff');
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

    expect($props->auth->staff)->not->toBeNull()
        ->and($props->auth->staff['user_type'])->toBe('tenant');
});

it('no user produces guest auth props with null user', function (): void {
    $request = Request::create('/test', 'GET');

    $composer = app(ComposeInertiaProps::class);
    $props = $composer->execute($request);

    expect($props->auth->user)->toBeNull()
        ->and($props->auth->staff)->toBeNull()
        ->and($props->auth->impersonate)->toBe(false);
});
