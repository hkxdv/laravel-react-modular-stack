<?php

declare(strict_types=1);

namespace Modules\Admin\App\ModuleConfig;

use Modules\Core\Contracts\ModuleConfigInterface;
use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbItem;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavComponentGroup;
use Modules\Core\Domain\Menu\NavComponentLink;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Configuración declarativa del módulo Admin.
 */
final class AdminModuleConfig implements ModuleConfigInterface
{
    public function addon(): AddonConfig
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('admin');

        return AddonConfig::fromArray('Admin', $config);
    }

    public function navItem(): ?NavItem
    {
        /** @var array<string, mixed>|null $nav */
        $nav = config('admin.nav_item');

        if (! is_array($nav)) {
            return null;
        }

        $title = $nav['title'] ?? '';
        $routeName = $nav['route_name'] ?? '';
        $icon = $nav['icon'] ?? '';
        $permission = $nav['permission'] ?? null;
        $showInNav = $nav['show_in_nav'] ?? true;
        $showInMainNav = $nav['show_in_main_nav'] ?? false;

        return new NavItem(
            title: is_string($title) ? $title : '',
            routeNameSuffix: is_string($routeName) ? $routeName : '',
            icon: is_string($icon) ? $icon : '',
            permission: is_string($permission) ? $permission : null,
            showInNav: is_bool($showInNav) ? $showInNav : true,
            showInMainNav: is_bool($showInMainNav) && $showInMainNav,
        );
    }

    public function contextualNav(): ContextualNavMap
    {
        $panel = $this->buildPanelLink();
        $usersList = $this->buildUsersListLink();
        $usersCreate = $this->buildUsersCreateLink();
        $backToPanel = $this->buildBackToPanelLink();
        $backToList = $this->buildBackToListLink();
        $rolesList = $this->buildRolesListLink();
        $this->buildPermissionsListLink();

        /** @var array<string, list<NavComponentLink|NavComponentGroup>> $items */
        $items = [
            'default' => [
                new NavComponentGroup(name: 'user_management', links: [$panel, $usersList, $usersCreate]),
            ],
            'users.index' => [$backToPanel, $usersCreate],
            'users.create' => [
                new NavComponentGroup(name: 'back_navigation', links: [$backToPanel, $backToList]),
            ],
            'users.edit' => [$backToPanel, $backToList],
            'roles.index' => [$backToPanel],
            'roles.create' => [$backToPanel, $rolesList],
            'roles.edit' => [$backToPanel, $rolesList],
            'permissions.index' => [$backToPanel],
        ];

        return ContextualNavMap::of($items);
    }

    public function breadcrumbs(): BreadcrumbMap
    {
        $adminRoot = $this->buildBreadcrumbAdminRoot();
        $usersList = $this->buildBreadcrumbUsersList();

        /** @var array<string, list<BreadcrumbItem>> $items */
        $items = [
            'default' => [$adminRoot],
            'users.index' => [$adminRoot, $usersList],
            'users.create' => [$adminRoot, $usersList, $this->buildBreadcrumbUsersCreate()],
            'users.edit' => [$adminRoot, $usersList, $this->buildBreadcrumbUsersEdit()],
            'roles.index' => [$adminRoot, $this->buildBreadcrumbRolesList()],
            'roles.create' => [$adminRoot, $this->buildBreadcrumbRolesList(), $this->buildBreadcrumbRolesCreate()],
            'roles.edit' => [$adminRoot, $this->buildBreadcrumbRolesList(), $this->buildBreadcrumbRolesEdit()],
            'permissions.index' => [$adminRoot, $this->buildBreadcrumbPermissionsList()],
        ];

        return new BreadcrumbMap($items);
    }

    /** @return list<PanelItem> */
    public function panelItems(): array
    {
        /** @var array<int, array<string, mixed>> $items */
        $items = (array) config('admin.panel_items', []);

        $result = [];
        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            $description = $item['description'] ?? '';
            $routeNameSuffix = $item['route_name_suffix'] ?? '';
            $icon = $item['icon'] ?? '';
            $permission = $item['permission'] ?? null;

            if (is_string($name) && is_string($routeNameSuffix) && $name !== '' && $routeNameSuffix !== '') {
                $result[] = new PanelItem(
                    name: $name,
                    description: is_string($description) ? $description : '',
                    routeNameSuffix: $routeNameSuffix,
                    icon: is_string($icon) ? $icon : '',
                    permission: is_string($permission) ? $permission : null,
                );
            }
        }

        return $result;
    }

    // ── Nav component links (shared definitions) ──

    private function buildPanelLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'panel',
            title: 'Módulo de Administración',
            routeNameSuffix: 'index',
            icon: 'LayoutDashboard',
            permission: 'rbac.view',
        );
    }

    private function buildUsersListLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'users_list',
            title: 'Lista de Usuarios',
            routeNameSuffix: 'users.index',
            icon: 'ScrollText',
            permission: 'staff-users.view',
        );
    }

    private function buildUsersCreateLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'users_create',
            title: 'Crear Usuario',
            routeNameSuffix: 'users.create',
            icon: 'UserPlus',
            permission: 'staff-users.create',
        );
    }

    private function buildBackToPanelLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'back_to_panel',
            title: 'Volver al panel',
            routeNameSuffix: 'index',
            icon: 'ArrowLeft',
            permission: 'rbac.view',
        );
    }

    private function buildBackToListLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'back_to_list',
            title: 'Volver a la lista',
            routeNameSuffix: 'users.index',
            icon: 'ArrowLeft',
            permission: 'staff-users.view',
        );
    }

    private function buildRolesListLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'roles_list',
            title: 'Gestión de Roles',
            routeNameSuffix: 'roles.index',
            icon: 'Shield',
            permission: 'roles.view',
        );
    }

    private function buildPermissionsListLink(): NavComponentLink
    {
        return new NavComponentLink(
            key: 'permissions_list',
            title: 'Permisos del Sistema',
            routeNameSuffix: 'permissions.index',
            icon: 'KeyRound',
            permission: 'permissions.view',
        );
    }

    // ── Breadcrumb components (shared definitions) ──

    private function buildBreadcrumbAdminRoot(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Módulo de Administración',
            routeNameSuffix: 'index',
        );
    }

    private function buildBreadcrumbUsersList(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Lista de Usuarios',
            routeNameSuffix: 'users.index',
        );
    }

    private function buildBreadcrumbUsersCreate(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Crear Usuario',
            routeNameSuffix: 'users.create',
        );
    }

    private function buildBreadcrumbUsersEdit(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Editar Usuario',
            routeNameSuffix: 'users.edit',
            dynamicTitleProp: 'user.name',
        );
    }

    private function buildBreadcrumbRolesList(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Gestión de Roles',
            routeNameSuffix: 'roles.index',
        );
    }

    private function buildBreadcrumbRolesCreate(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Crear Rol',
            routeNameSuffix: 'roles.create',
        );
    }

    private function buildBreadcrumbRolesEdit(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Editar Rol',
            routeNameSuffix: 'roles.edit',
            dynamicTitleProp: 'role.name',
        );
    }

    private function buildBreadcrumbPermissionsList(): BreadcrumbItem
    {
        return new BreadcrumbItem(
            title: 'Permisos del Sistema',
            routeNameSuffix: 'permissions.index',
        );
    }
}
