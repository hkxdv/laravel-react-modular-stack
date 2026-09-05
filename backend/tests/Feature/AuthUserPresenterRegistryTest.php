<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Admin\App\Models\StaffUser;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Contracts\Auth\AuthUserPresenterRegistryInterface;
use Modules\Examples\App\Models\ExampleTenantUser;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;

uses(RefreshDatabase::class);

it('returns staff presenter when staff user is authenticated', function (): void {
    $registry = app(AuthUserPresenterRegistryInterface::class);
    $user = StaffUsersFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'staff' ? $user : null;
    });

    $resolved = $registry->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->user)->toBeInstanceOf(StaffUser::class)
        ->and(get_class($resolved->presenter))->toContain('StaffUserPresenter');
});

it('returns tenant presenter when only tenant user is authenticated', function (): void {
    $registry = app(AuthUserPresenterRegistryInterface::class);
    $user = ExampleTenantUserFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($user) {
        return $guard === 'tenant' ? $user : null;
    });

    $resolved = $registry->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->user)->toBeInstanceOf(ExampleTenantUser::class)
        ->and(get_class($resolved->presenter))->toContain('TenantUserPresenter');
});

it('returns null when no user is authenticated', function (): void {
    $registry = app(AuthUserPresenterRegistryInterface::class);

    $request = Request::create('/test', 'GET');

    $resolved = $registry->resolve($request);

    expect($resolved)->toBeNull();
});

it('prefers staff over tenant when both are authenticated', function (): void {
    $registry = app(AuthUserPresenterRegistryInterface::class);
    $staffUser = StaffUsersFactory::new()->create();
    $tenantUser = ExampleTenantUserFactory::new()->create();

    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function ($guard = null) use ($staffUser, $tenantUser) {
        if ($guard === 'staff') {
            return $staffUser;
        }

        return $guard === 'tenant' ? $tenantUser : null;
    });

    $resolved = $registry->resolve($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->user)->toBeInstanceOf(StaffUser::class)
        ->and(get_class($resolved->presenter))->toContain('StaffUserPresenter');
});
