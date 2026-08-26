<?php

declare(strict_types=1);

use Modules\Core\Infrastructure\Laravel\Services\AuthService;

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
