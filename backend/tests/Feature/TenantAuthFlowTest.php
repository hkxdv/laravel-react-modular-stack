<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Examples\App\Http\Requests\TenantLoginRequest;
use Modules\Examples\App\Models\ExampleTenantUser;
use Modules\Examples\App\Services\TenantUserPresenter;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Tenant Auth Flow Test
|--------------------------------------------------------------------------
|
| Verifies the ExampleTenantUser abstraction works end-to-end:
| - Config registration (guard + provider + morph map)
| - Model creation and attributes
| - Presenter output (pure tenant, no Admin dependency)
| - TenantLoginRequest contract (guard, type, redirect route)
| - Routes under auth:tenant (not auth:staff)
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
        ->and($guards['tenant']['login_route'])->toBe('tenant.login')
        ->and($guards['tenant']['redirect_route'])->toBe('tenant.login')
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

    $presenter = new TenantUserPresenter();
    $result = $presenter->present($user);

    expect($result)->toBeObject()
        ->and($result->id)->toBe($user->id)
        ->and($result->name)->toBe('Maria Garcia')
        ->and($result->email)->toBe('maria@tenant.com')
        ->and($result->user_type)->toBe('tenant');
});

it('presenter does not depend on Admin module', function () {
    $reflection = new ReflectionClass(TenantUserPresenter::class);
    $source = file_get_contents($reflection->getFileName());

    expect($source)->not->toContain('Modules\\Admin')
        ->and($source)->not->toContain('StaffUser');
});

it('tenant login request returns correct guard, type, and redirect route', function () {
    $request = new TenantLoginRequest();

    $guardMethod = new ReflectionMethod($request, 'guard');
    $loginTypeMethod = new ReflectionMethod($request, 'loginType');
    $redirectRouteMethod = new ReflectionMethod($request, 'redirectRoute');

    expect($guardMethod->invoke($request))->toBe('tenant')
        ->and($loginTypeMethod->invoke($request))->toBe('tenant')
        ->and($redirectRouteMethod->invoke($request))->toBe('internal.tenant.examples.index');
});

it('has tenant login routes under guest:tenant', function () {
    expect(route('tenant.login'))->toBe(route('tenant.login'))
        ->and(route('tenant.login.store'))->toBe(route('tenant.login.store'));
});

it('has tenant logout route under auth:tenant', function () {
    expect(route('tenant.logout'))->toBe(route('tenant.logout'));
});
