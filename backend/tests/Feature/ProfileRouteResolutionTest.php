<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ── AUTC-S3: rutas de perfil resuelven con los mismos nombres tras el
// traslado de controladores a App\Http\Controllers\Profile ──

it('todas las rutas de perfil siguen existiendo con los mismos nombres', function (): void {
    $expectedNames = [
        'internal.staff.root.redirect',
        'internal.staff.profile.edit',
        'internal.staff.profile.update',
        'internal.staff.profile.destroy',
        'internal.staff.password.edit',
        'internal.staff.password.update',
        'internal.staff.appearance',
        'internal.staff.security.edit',
        'internal.staff.security.sessions.revoke',
        'internal.staff.security.two-factor.setup',
        'internal.staff.security.two-factor.confirm',
        'internal.staff.security.two-factor.disable',
        'internal.staff.security.two-factor.recovery-codes',
        'internal.staff.notifications.edit',
        'internal.staff.notifications.update',
    ];

    foreach ($expectedNames as $name) {
        expect(route($name))->toBeString();
    }
});

it('los controladores de perfil se resuelven desde App\\Http\\Controllers\\Profile', function (): void {
    $expected = [
        'internal.staff.profile.edit' => 'App\\Http\\Controllers\\Profile\\ProfileController',
        'internal.staff.password.edit' => 'App\\Http\\Controllers\\Profile\\PasswordController',
        'internal.staff.appearance' => 'App\\Http\\Controllers\\Profile\\AppearanceController',
        'internal.staff.security.edit' => 'App\\Http\\Controllers\\Profile\\AccountSecurityController',
        'internal.staff.notifications.edit' => 'App\\Http\\Controllers\\Profile\\NotificationPreferencesController',
    ];

    foreach ($expected as $name => $controller) {
        /** @var Router $router */
        $router = app(Router::class);
        $route = $router->getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->getActionName())->toStartWith($controller.'@');
    }
});

it('un staff autenticado obtiene 200 en la edición de perfil con resolución real de guard', function (): void {
    $role = Role::query()->create(['name' => 'staff', 'guard_name' => 'staff']);
    $user = StaffUsersFactory::new()->create()->fresh();
    $user->assignRole($role);

    $this->actingAs($user, 'staff');

    $response = $this->get(route('internal.staff.profile.edit'));

    $response->assertOk();
});
