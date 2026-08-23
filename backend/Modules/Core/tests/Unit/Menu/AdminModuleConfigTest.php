<?php

declare(strict_types=1);

use Modules\Admin\App\ModuleConfig\AdminModuleConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Core\Domain\Panel\PanelItem;

uses(Tests\TestCase::class);

it('contextualNav returns ContextualNavMap with correct structure from config', function (): void {
    $adapter = new AdminModuleConfig();
    $result = $adapter->contextualNav();

    expect($result)->toBeInstanceOf(ContextualNavMap::class)
        ->and($result->items)->not->toBeEmpty();

    // default route uses group:user_management
    expect($result->items)->toHaveKey('default');
    $defaultItems = $result->items['default'];
    expect($defaultItems)->not->toBeEmpty();

    // user_management group resolves to NavComponentGroup
    $group = array_first($defaultItems);
    assert($group instanceof NavComponentGroup);
    expect($group->name)->toBe('user_management')
        ->and($group->links)->not->toBeEmpty();

    // users.index route has plain link keys
    expect($result->items)->toHaveKey('users.index');
    $usersIndexItems = $result->items['users.index'];
    expect($usersIndexItems)->toHaveCount(2);
    assert($usersIndexItems[0] instanceof NavComponentLink);
    expect($usersIndexItems[0]->key)->toBe('back_to_panel');
    assert($usersIndexItems[1] instanceof NavComponentLink);
    expect($usersIndexItems[1]->key)->toBe('users_create');
});

it('breadcrumbs returns BreadcrumbMap with correct structure from config', function (): void {
    $adapter = new AdminModuleConfig();
    $result = $adapter->breadcrumbs();

    expect($result)->toBeInstanceOf(BreadcrumbMap::class)
        ->and($result->items)->not->toBeEmpty();

    // users.index has admin_root + users_list
    expect($result->items)->toHaveKey('users.index');
    $crumbs = $result->items['users.index'];
    expect($crumbs)->toHaveCount(2)
        ->and($crumbs[0]->title)->toBe('Módulo de Administración')
        ->and($crumbs[1]->title)->toBe('Lista de Usuarios');

    // users.edit has dynamic_title_prop preserved
    expect($result->items)->toHaveKey('users.edit');
    $editCrumbs = $result->items['users.edit'];
    expect($editCrumbs[2]->dynamicTitleProp)->toBe('user.name');
});

it('panelItems returns list of PanelItem from config', function (): void {
    $adapter = new AdminModuleConfig();
    $result = $adapter->panelItems();

    expect($result)->toBeArray()
        ->and($result)->not->toBeEmpty()
        ->and($result)->toHaveCount(3);

    $first = $result[0];
    expect($first)->toBeInstanceOf(PanelItem::class)
        ->and($first->name)->toBe('Lista de Usuarios')
        ->and($first->description)->toBe('Añadir, editar o eliminar cuentas de usuario.')
        ->and($first->routeNameSuffix)->toBe('users.index')
        ->and($first->icon)->toBe('Users')
        ->and($first->permission)->toBe('staff-users.view');

    $second = $result[1];
    expect($second->name)->toBe('Gestión de Roles')
        ->and($second->permission)->toBe('roles.view');

    $third = $result[2];
    expect($third->name)->toBe('Permisos del Sistema')
        ->and($third->permission)->toBe('permissions.view');
});

it('navItem returns NavItem with fallbackTitle applied from config', function (): void {
    $adapter = new AdminModuleConfig();
    $result = $adapter->navItem();

    assert($result instanceof Modules\Core\Domain\Menu\NavItem);
    expect($result->title)->toBe('Módulo de Administración')
        ->and($result->routeNameSuffix)->toBe('internal.staff.admin.index')
        ->and($result->icon)->toBe('ShieldCheck')
        ->and($result->showInNav)->toBeTrue();
});

it('addon returns AddonConfig from config', function (): void {
    $adapter = new AdminModuleConfig();
    $result = $adapter->addon();

    expect($result->moduleSlug)->toBe('admin')
        ->and($result->functionalName)->toBe('Módulo de Administración');
});
