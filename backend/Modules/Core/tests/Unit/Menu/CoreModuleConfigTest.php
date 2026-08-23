<?php

declare(strict_types=1);

use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Core\Infrastructure\Laravel\Services\CoreModuleConfig;

uses(Tests\TestCase::class);

it('contextualNav returns ContextualNavMap with user_profile_nav group from config', function (): void {
    $adapter = new CoreModuleConfig();
    $result = $adapter->contextualNav();

    expect($result)->toBeInstanceOf(ContextualNavMap::class)
        ->and($result->items)->toHaveKey('default');

    $defaultItems = $result->items['default'];
    expect($defaultItems)->toHaveCount(1);

    $group = $defaultItems[0];
    assert($group instanceof NavComponentGroup);
    expect($group->name)->toBe('user_profile_nav')
        ->and($group->links)->toHaveCount(5);

    // Verify all links in group are NavComponentLink instances
    foreach ($group->links as $link) {
        expect($link)->toBeInstanceOf(NavComponentLink::class);
    }

    // Verify specific links exist
    $linkKeys = array_map(fn (NavComponentLink $l): string => $l->key, $group->links);
    expect($linkKeys)->toContain('profile')
        ->and($linkKeys)->toContain('password')
        ->and($linkKeys)->toContain('appearance')
        ->and($linkKeys)->toContain('account_security')
        ->and($linkKeys)->toContain('notification_preferences');
});

it('breadcrumbs returns BreadcrumbMap with profile routes from config', function (): void {
    $adapter = new CoreModuleConfig();
    $result = $adapter->breadcrumbs();

    expect($result)->toBeInstanceOf(BreadcrumbMap::class)
        ->and($result->items)->not->toBeEmpty();

    // profile.edit has user_profile_root + user_profile_profile
    expect($result->items)->toHaveKey('profile.edit');
    $crumbs = $result->items['profile.edit'];
    expect($crumbs)->toHaveCount(2)
        ->and($crumbs[0]->title)->toBe('Configuración')
        ->and($crumbs[1]->title)->toBe('Perfil');

    // password.edit has user_profile_root + user_profile_password
    expect($result->items)->toHaveKey('password.edit');
    $pwdCrumbs = $result->items['password.edit'];
    expect($pwdCrumbs)->toHaveCount(2)
        ->and($pwdCrumbs[1]->title)->toBe('Contraseña');

    // notifications.edit route
    expect($result->items)->toHaveKey('notifications.edit');
    $notifCrumbs = $result->items['notifications.edit'];
    expect($notifCrumbs[1]->title)->toBe('Notificaciones');
});

it('navItem returns null (Core has no main nav item)', function (): void {
    $adapter = new CoreModuleConfig();
    $result = $adapter->navItem();

    expect($result)->toBeNull();
});

it('panelItems returns empty array (Core has no panel items)', function (): void {
    $adapter = new CoreModuleConfig();
    $result = $adapter->panelItems();

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

it('addon returns AddonConfig from config', function (): void {
    $adapter = new CoreModuleConfig();
    $result = $adapter->addon();

    expect($result->moduleSlug)->toBe('core')
        ->and($result->functionalName)->toBe('Core');
});
