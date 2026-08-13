<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Modules\Core\Infrastructure\Laravel\Services\SecurityAuditService;
use Modules\Core\Tests\Fakes\SessionStoreFake;

uses(Tests\TestCase::class, SessionStoreFake::class);

// ── CT-SEC-01: prepareAuthenticatedSession regenerates session ──

it('regenerates session ID and saves session on prepareAuthenticatedSession', function (): void {
    $agent = new Agent();
    $service = new SecurityAuditService($agent);

    $request = Request::create('/login', 'POST');
    $store = $this->createFakeSession($request);

    $originalId = $store->getId();

    $service->prepareAuthenticatedSession($request);

    expect($store->getId())->not->toBe($originalId);
});

// ── CT-SEC-02: handleSuspiciousLoginNotification logs on exception ──

it('logs warning when suspicious login notification throws an exception', function (): void {
    // StaffUser is final — need real instance via factory.
    // Configure SQLite in-memory for this test.
    config(['database.default' => 'sqlite']);
    config(['database.connections.sqlite.database' => ':memory:']);
    $this->artisan('migrate:fresh', ['--quiet' => true]);

    $user = StaffUser::factory()->create();

    // Seed login history: 2+ entries, none trusted → isSuspiciousLogin returns true
    $user->loginInfos()->create([
        'ip_address' => '10.0.0.1',
        'user_agent' => 'OldAgent/1.0',
        'is_trusted' => false,
        'last_login_at' => now()->subDay(),
    ]);
    $user->loginInfos()->create([
        'ip_address' => '10.0.0.2',
        'user_agent' => 'AnotherAgent/2.0',
        'is_trusted' => false,
        'last_login_at' => now()->subHours(2),
    ]);

    // Mock Agent to provide device info
    $agent = Mockery::mock(Agent::class);
    $agent->shouldReceive('setUserAgent')->once();
    $agent->shouldReceive('device')->andReturn('Desktop');
    $agent->shouldReceive('platform')->andReturn('Linux');
    $agent->shouldReceive('browser')->andReturn('Firefox');
    $agent->shouldReceive('isMobile')->andReturn(false);

    // Mock notification Dispatcher to throw — Notifiable::notify() resolves this from container
    $dispatcher = Mockery::mock(Illuminate\Contracts\Notifications\Dispatcher::class);
    $dispatcher->shouldReceive('send')->andThrow(new RuntimeException('Notification service unavailable'));
    $this->app->instance(Illuminate\Contracts\Notifications\Dispatcher::class, $dispatcher);

    // Spy on the security_core log channel
    $logSpy = Mockery::spy(Psr\Log\LoggerInterface::class);
    Log::shouldReceive('channel')->with('security_core')->andReturn($logSpy);

    $service = new SecurityAuditService($agent);

    $request = Request::create('/login', 'POST');
    $request->headers->set('User-Agent', 'TestAgent/1.0');

    // Should NOT propagate the exception
    $service->handleSuspiciousLoginNotification($user, $request);

    // Assert warning was logged with correct context
    $logSpy->shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => str_contains($message, (string) $user->getAuthIdentifier())
            && isset($context['error'])
            && $context['error'] === 'Notification service unavailable'
            && isset($context['trace']));
});

// ── CT-SEC-03: logout invalidates session and removes cookie ──

it('invalidates session, regenerates token, forgets keys, and removes cookie on logout', function (): void {
    $agent = new Agent();

    // Mock Auth::guard('staff')->logout()
    $guard = Mockery::spy(Illuminate\Contracts\Auth\StatefulGuard::class);
    Auth::shouldReceive('guard')->with('staff')->andReturn($guard);

    $service = new SecurityAuditService($agent);

    $request = Request::create('/logout', 'POST');
    $store = $this->createFakeSession($request);

    // Put values that should be forgotten
    $store->put('auth', 'some_value');
    $store->put('auth.password_confirmed_at', now()->toDateTimeString());
    $store->save();

    // Configure a session cookie name so the cookie-removal branch triggers
    config(['session.cookie' => 'foundry_session']);
    $request->cookies->set('foundry_session', 'cookie_value');

    $service->logout($request, 'staff');

    // Guard logout was called
    $guard->shouldHaveReceived('logout');

    // Session should have auth keys removed
    expect($store->get('auth'))->toBeNull();
    expect($store->get('auth.password_confirmed_at'))->toBeNull();

    // Cookie should be removed
    expect($request->cookies->get('foundry_session'))->toBeNull();
});
