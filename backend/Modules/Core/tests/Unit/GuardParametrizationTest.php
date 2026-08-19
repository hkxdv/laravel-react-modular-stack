<?php

declare(strict_types=1);

use Modules\Core\Infrastructure\Laravel\Services\AuthService;

uses(Tests\TestCase::class);

// ── T2a-6: Guard parametrization tests ──

it('has guards config with staff, web, and sanctum keys', function (): void {
    /** @var array<string, mixed> $guards */
    $guards = config('core.guards');

    expect($guards)->toBeArray()
        ->toHaveKeys(['staff', 'web', 'sanctum']);
});

it('guards config has login_route, redirect_route, and provider per guard', function (): void {
    /** @var array<string, array<string, string>> $guards */
    $guards = config('core.guards');

    foreach ($guards as $config) {
        expect($config)->toHaveKeys(['login_route', 'redirect_route', 'provider'])
            ->and($config['login_route'])->toBeString()
            ->and($config['redirect_route'])->toBeString()
            ->and($config['provider'])->toBeString();
    }
});

it('has sync_excludes containing staff', function (): void {
    /** @var array<string> $syncExcludes */
    $syncExcludes = config('core.sync_excludes');

    expect($syncExcludes)->toBeArray()
        ->toContain('staff');
});

it('authservice forguard returns different instances per guard', function (): void {
    $staffAuth = AuthService::forGuard('staff');
    $webAuth = AuthService::forGuard('web');

    expect($staffAuth)->not->toBe($webAuth);
});

it('authservice forguard returns self', function (): void {
    $auth = AuthService::forGuard('staff');

    expect($auth)->toBeInstanceOf(AuthService::class);
});

it('getavailableguards reads from config', function (): void {
    $reflection = new ReflectionMethod(
        Modules\Core\Infrastructure\Eloquent\Models\StaffUser::class,
        'getAvailableGuards'
    );

    $staffUser = new Modules\Core\Infrastructure\Eloquent\Models\StaffUser();
    /** @var array<string> $guards */
    $guards = $reflection->invoke($staffUser);

    expect($guards)->toBeArray()
        ->toContain('staff')
        ->toContain('web')
        ->toContain('sanctum');
});

it('stopimpersonating reads correct config key', function (): void {
    $model = config('auth.providers.staff.model');

    expect($model)->toBe(Modules\Core\Infrastructure\Eloquent\Models\StaffUser::class);
});
