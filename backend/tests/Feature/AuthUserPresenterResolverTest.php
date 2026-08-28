<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\AuthUserPresenterResolver;
use Illuminate\Http\Request;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;

it('returns staff presenter when staff user is authenticated', function (): void {
    $resolver = app(AuthUserPresenterResolver::class);
    $user = StaffUsersFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'staff' ? $user : null;
    });

    $presenter = $resolver->resolve($request);

    expect($presenter)->not->toBeNull()
        ->and(get_class($presenter))->toContain('StaffUserPresenter');
});

it('returns tenant presenter when only tenant user is authenticated', function (): void {
    $resolver = app(AuthUserPresenterResolver::class);
    $user = ExampleTenantUserFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'tenant' ? $user : null;
    });

    $presenter = $resolver->resolve($request);

    expect($presenter)->not->toBeNull()
        ->and(get_class($presenter))->toContain('TenantUserPresenter');
});

it('returns null when no user is authenticated', function (): void {
    $resolver = app(AuthUserPresenterResolver::class);

    $request = Request::create('/test', 'GET');

    $presenter = $resolver->resolve($request);

    expect($presenter)->toBeNull();
});

it('prefers staff over tenant when both are authenticated', function (): void {
    $resolver = app(AuthUserPresenterResolver::class);
    $staffUser = StaffUsersFactory::new()->create();
    $tenantUser = ExampleTenantUserFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($staffUser, $tenantUser) {
        if ($guard === 'staff') {
            return $staffUser;
        }

        return $guard === 'tenant' ? $tenantUser : null;
    });

    $presenter = $resolver->resolve($request);

    expect($presenter)->not->toBeNull()
        ->and(get_class($presenter))->toContain('StaffUserPresenter');
});
