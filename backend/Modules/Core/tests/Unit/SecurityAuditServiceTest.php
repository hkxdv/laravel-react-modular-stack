<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Modules\Core\Infrastructure\Laravel\Services\SecurityAuditService;
use Modules\Core\Tests\Fakes\SessionStoreFake;

uses(SessionStoreFake::class);

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
