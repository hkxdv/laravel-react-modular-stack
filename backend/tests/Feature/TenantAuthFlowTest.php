<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Examples\App\Models\ExampleTenantUser;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Tenant Auth Flow Test
|--------------------------------------------------------------------------
|
| Verifies the ExampleTenantUser abstraction works end-to-end:
| - Config registration (guard + provider + morph map)
| - Model creation and attributes
| - Presenter output
| - TenantLoginRequest contract
|
*/

it('has tenant guard registered in auth config', function () {
    $guards = config('auth.guards');

    expect($guards)->toHaveKey('tenant')
        ->and($guards['tenant']['driver'])->toBe('session')
        ->and($guards['tenant']['provider'])->toBe('tenant');
});

it('has tenant provider registered in auth config', function () {
    $providers = config('auth.providers');

    expect($providers)->toHaveKey('tenant')
        ->and($providers['tenant']['driver'])->toBe('eloquent')
        ->and($providers['tenant']['model'])->toBe(ExampleTenantUser::class);
});

it('has tenant entry in core guards config', function () {
    $guards = config('core.guards');

    expect($guards)->toHaveKey('tenant')
        ->and($guards['tenant']['login_route'])->toBe('login')
        ->and($guards['tenant']['redirect_route'])->toBe('login')
        ->and($guards['tenant']['provider'])->toBe('tenant');
});

it('has tenant-user in morph map', function () {
    $morphMap = Relation::morphMap();

    expect($morphMap)->toHaveKey('tenant-user')
        ->and($morphMap['tenant-user'])->toBe(ExampleTenantUser::class);
});

it('can create an ExampleTenantUser via factory', function () {
    $user = ExampleTenantUser::factory()->create([
        'name' => 'Test Tenant',
        'email' => 'tenant@example.com',
    ]);

    expect($user)->toBeInstanceOf(ExampleTenantUser::class)
        ->and($user->name)->toBe('Test Tenant')
        ->and($user->email)->toBe('tenant@example.com')
        ->and($user->password)->not->toBe('password'); // Hashed
});

it('returns tenant as auth guard', function () {
    $user = ExampleTenantUser::factory()->create();

    expect($user->getAuthGuard())->toBe('tenant');
});

it('returns name as display name', function () {
    $user = ExampleTenantUser::factory()->create(['name' => 'Juan Perez']);

    expect($user->getDisplayName())->toBe('Juan Perez');
});

it('presenter returns tenant user type for ExampleTenantUser', function () {
    $user = ExampleTenantUser::factory()->create([
        'name' => 'Maria Garcia',
        'email' => 'maria@tenant.com',
    ]);

    $presenter = new Modules\Examples\App\Services\TenantUserPresenter();
    $result = $presenter->present($user);

    expect($result)->toBeArray()
        ->and($result['id'])->toBe($user->id)
        ->and($result['name'])->toBe('Maria Garcia')
        ->and($result['email'])->toBe('maria@tenant.com')
        ->and($result['user_type'])->toBe('tenant');
});

it('presenter delegates to staff presenter for StaffUser', function () {
    $staffUser = Modules\Admin\App\Models\StaffUser::factory()->create();

    $presenter = new Modules\Examples\App\Services\TenantUserPresenter();
    $result = $presenter->present($staffUser);

    // StaffUserPresenter returns a StaffUserResource array, which has 'id' and 'name'
    expect($result)->toBeArray()
        ->and($result)->toHaveKey('id')
        ->and($result)->toHaveKey('name');
});

it('tenant login request returns correct guard and type', function () {
    $request = new Modules\Examples\App\Http\Requests\TenantLoginRequest();

    $guardMethod = new ReflectionMethod($request, 'guard');
    $loginTypeMethod = new ReflectionMethod($request, 'loginType');
    $redirectRouteMethod = new ReflectionMethod($request, 'redirectRoute');

    expect($guardMethod->invoke($request))->toBe('tenant')
        ->and($loginTypeMethod->invoke($request))->toBe('tenant')
        ->and($redirectRouteMethod->invoke($request))->toBe('internal.tenant.dashboard');
});
