<?php

declare(strict_types=1);

use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Infrastructure\Laravel\Services\AuthService;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;

use function Foundry\Helpers\configString;

uses(RefreshDatabase::class);

// ── T2a-6: Guard parametrization tests ──

it('has guards config with staff, web, and sanctum keys', function (): void {
    /** @var array<string, mixed> $guards */
    $guards = config('core.guards');

    expect($guards)->toHaveKeys(['staff', 'web', 'sanctum']);
});

it('guards config has login_route, redirect_route, and provider per guard', function (): void {
    /** @var array<string, array<string, string>> $guards */
    $guards = config('core.guards');

    expect($guards)->each->toHaveKeys(['login_route', 'redirect_route', 'provider']);
});

it('has sync_excludes containing staff', function (): void {
    /** @var array<string> $syncExcludes */
    $syncExcludes = config('core.sync_excludes');

    expect($syncExcludes)->toContain('staff');
});

it('authservice forguard returns different instances per guard', function (): void {
    $staffAuth = AuthService::forGuard('staff');
    $webAuth = AuthService::forGuard('web');

    expect($staffAuth)->not->toBe($webAuth);
});

it('authservice forguard returns self', function (): void {
    $auth = AuthService::forGuard('staff');

    $guard = new ReflectionClass(AuthService::class)->getProperty('guard')->getValue($auth);

    expect($guard)->toBe('staff');
});

it('container resolution resolves the guard from the active user (D1)', function (): void {
    $user = StaffUsersFactory::new()->createOne();

    // El closure usa auth()->user(): el guard por defecto de la app.
    Auth::guard(configString('auth.defaults.guard', 'web'))->setUser($user);

    $auth = resolve(AuthService::class);

    $guard = new ReflectionClass(AuthService::class)->getProperty('guard')->getValue($auth);

    expect($guard)->toBe('staff');
});

it('container resolution falls back to the default guard without a user (D1)', function (): void {
    $auth = resolve(AuthService::class);

    $guard = new ReflectionClass(AuthService::class)->getProperty('guard')->getValue($auth);

    expect($guard)->toBe(configString('auth.defaults.guard', 'web'));
});

it('getavailableguards reads from config for any domain user', function (): void {
    $reflection = new ReflectionMethod(
        Modules\Core\Tests\Fakes\FakeDomainUser::class,
        'getAvailableGuards'
    );

    $user = new Modules\Core\Tests\Fakes\FakeDomainUser();
    /** @var array<string> $guards */
    $guards = $reflection->invoke($user);

    expect($guards)->toContain('web')
        ->toContain('sanctum');
});

it('stopimpersonating reads correct config key', function (): void {
    $model = config('auth.providers.staff.model');

    expect($model)->toBeString()
        ->and($model)->not->toBe('');
});

// ── DECOUPLE-G1: stopImpersonating resolves the model from the active guard ──

it('stopimpersonating resolves tenant model from auth.providers.tenant.model', function (): void {
    $user = ExampleTenantUserFactory::new()->createOne([
        'name' => 'Tenant Original',
        'email' => 'tenant-original@example.com',
    ]);

    resolve(Session::class)->start();
    session()->put('impersonated_by', $user->getAuthIdentifier());

    $auth = AuthService::forGuard('tenant');

    expect($auth->stopImpersonating())->toBeTrue()
        ->and(Auth::guard('tenant')->user()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});

it('stopimpersonating resolves staff model from auth.providers.staff.model', function (): void {
    $user = StaffUsersFactory::new()->createOne();

    resolve(Session::class)->start();
    session()->put('impersonated_by', $user->getAuthIdentifier());

    $auth = AuthService::forGuard('staff');

    expect($auth->stopImpersonating())->toBeTrue()
        ->and(Auth::guard('staff')->user()?->getAuthIdentifier())->toBe($user->getAuthIdentifier());
});

it('stopimpersonating returns false when the original user is missing', function (): void {
    resolve(Session::class)->start();
    session()->put('impersonated_by', 999999);

    $auth = AuthService::forGuard('staff');

    expect($auth->stopImpersonating())->toBeFalse();
});
