<?php

declare(strict_types=1);

namespace Modules\Examples\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;
use PHPUnit\Framework\SkippedTestSuiteError;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Espejo del estado producido por `permissions:sync` en producción:
    // los permisos tenant registrados por ExamplesPermissionRegistry existen
    // como registros pero NO están asignados a ningún usuario.
    foreach (['examples.tenant.dashboard', 'examples.tenant.manage', 'examples.tenant.logout'] as $name) {
        Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'tenant']);
    }
});

it('denies tenant dashboard to an authenticated tenant without permission', function (): void {
    $user = ExampleTenantUserFactory::new()->createOne();
    $this->actingAs($user, 'tenant');

    $this->get('/internal/tenant/examples')
        ->assertForbidden();
});

it('allows tenant dashboard to a tenant with the dashboard permission', function (): void {
    $user = ExampleTenantUserFactory::new()->createOne();
    $user->givePermissionTo('examples.tenant.dashboard');

    $this->actingAs($user, 'tenant');

    $this->get('/internal/tenant/examples')
        ->assertOk();
});

it('redirects guests away from the tenant dashboard', function (): void {
    // GAP-9 workaround (gaps-pendientes #2): root/module routes are not
    // re-registered on app re-boot inside the same Pest process, so the
    // guest redirect target (route 'login') may be unavailable mid-suite.
    throw_unless(
        Route::has('login'),
        SkippedTestSuiteError::class,
        'GAP-9: rutas no re-registradas en re-boot de suite (gaps-pendientes #2)'
    );

    $this->get('/internal/tenant/examples')
        ->assertRedirect();
});

it('denies tenant logout to a tenant without the logout permission', function (): void {
    $user = ExampleTenantUserFactory::new()->createOne();
    $this->actingAs($user, 'tenant');

    $this->post('/internal/tenant/logout')
        ->assertForbidden();
});
