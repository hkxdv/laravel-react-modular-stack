<?php

declare(strict_types=1);

$usersListTitle = 'Lista de Usuarios';
$usersListRoute = 'users.index';
$adminRoot = [
    'title' => 'Módulo de Administración',
    'route_name_suffix' => 'index',
];
$usersList = [
    'title' => $usersListTitle,
    'route_name_suffix' => $usersListRoute,
];
$backToPanelRef = '$ref:nav_components.links.back_to_panel';
$adminRootRef = '$ref:breadcrumb_components.admin_root';
$usersListRef = '$ref:breadcrumb_components.users_list';

return [
    // Configuración básica del módulo
    'module_slug' => 'admin',
    'inertia_view_directory' => 'admin',
    'auth_guard' => 'staff',
    'functional_name' => 'Módulo de Administración',
    'description' => 'Explora las opciones de administración del sistema y revisa las estadísticas clave.',
    'base_permission' => 'rbac.view',

    // Configuración del ítem de navegación principal
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.staff.admin.index',
        'icon' => 'ShieldCheck',
    ],

    // Componentes reutilizables de navegación (bloques para construir la navegación)
    'nav_components' => [
        // Enlaces individuales reutilizables
        'links' => [
            'panel' => [
                'title' => 'Módulo de Administración',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'rbac.view',
            ],
            'users_list' => [
                ...$usersList,
                'icon' => 'ScrollText',
                'permission' => 'staff-users.view',
            ],
            'users_create' => [
                'title' => 'Crear Usuario',
                'route_name_suffix' => 'users.create',
                'icon' => 'UserPlus',
                'permission' => 'staff-users.create',
            ],
            'back_to_panel' => [
                'title' => 'Volver al panel',
                'route_name_suffix' => 'index',
                'icon' => 'ArrowLeft',
                'permission' => 'rbac.view',
            ],
            'back_to_list' => [
                'title' => 'Volver a la lista',
                'route_name_suffix' => $usersListRoute,
                'icon' => 'ArrowLeft',
                'permission' => 'staff-users.view',
            ],
            'roles_list' => [
                'title' => 'Gestión de Roles',
                'route_name_suffix' => 'roles.index',
                'icon' => 'Shield',
                'permission' => 'roles.view',
            ],
            'permissions_list' => [
                'title' => 'Permisos del Sistema',
                'route_name_suffix' => 'permissions.index',
                'icon' => 'KeyRound',
                'permission' => 'permissions.view',
            ],
        ],

        // Grupos comunes de enlaces para reutilizar
        'groups' => [
            'admin_panel_nav' => [
                '$ref:nav_components.links.users_list',
            ],
            'user_management' => [
                '$ref:nav_components.links.panel',
                '$ref:nav_components.links.users_list',
                '$ref:nav_components.links.users_create',
            ],
            'back_navigation' => [
                '$ref:nav_components.links.back_to_panel',
                '$ref:nav_components.links.back_to_list',
            ],
        ],
    ],

    // Configuración de navegación contextual
    'contextual_nav' => [
        'default' => [
            '$ref:nav_components.groups.user_management',
        ],

        // Rutas para la gestión de usuarios
        'users.index' => [
            $backToPanelRef,
            '$ref:nav_components.links.users_create',
        ],
        'users.create' => [
            '$ref:nav_components.groups.back_navigation',
        ],
        'users.edit' => [
            $backToPanelRef,
            '$ref:nav_components.links.back_to_list',
        ],

        // Rutas para la gestión de roles
        'roles.index' => [
            $backToPanelRef,
        ],
        'roles.create' => [
            $backToPanelRef,
            '$ref:nav_components.links.roles_list',
        ],
        'roles.edit' => [
            $backToPanelRef,
            '$ref:nav_components.links.roles_list',
        ],

        // Rutas para permisos
        'permissions.index' => [
            $backToPanelRef,
        ],

    ],

    // Configuración de ítems del panel
    'panel_items' => [
        [
            'name' => $usersListTitle,
            'description' => 'Añadir, editar o eliminar cuentas de usuario.',
            'route_name_suffix' => $usersListRoute,
            'icon' => 'Users',
            'permission' => 'staff-users.view',
        ],
        [
            'name' => 'Gestión de Roles',
            'description' => 'Crear, editar y eliminar roles del sistema.',
            'route_name_suffix' => 'roles.index',
            'icon' => 'Shield',
            'permission' => 'roles.view',
        ],
        [
            'name' => 'Permisos del Sistema',
            'description' => 'Consultar permisos granulares por módulo.',
            'route_name_suffix' => 'permissions.index',
            'icon' => 'KeyRound',
            'permission' => 'permissions.view',
        ],
    ],

    // Componentes reutilizables de breadcrumbs
    'breadcrumb_components' => [
        'admin_root' => [
            ...$adminRoot,
        ],
        'users_list' => [
            ...$usersList,
        ],
        'users_create' => [
            'title' => 'Crear Usuario',
            'route_name_suffix' => 'users.create',
        ],
        'users_edit' => [
            'title' => 'Editar Usuario',
            'route_name_suffix' => 'users.edit',
            'dynamic_title_prop' => 'user.name',
        ],
        'roles_list' => [
            'title' => 'Gestión de Roles',
            'route_name_suffix' => 'roles.index',
        ],
        'roles_create' => [
            'title' => 'Crear Rol',
            'route_name_suffix' => 'roles.create',
        ],
        'roles_edit' => [
            'title' => 'Editar Rol',
            'route_name_suffix' => 'roles.edit',
            'dynamic_title_prop' => 'role.name',
        ],
        'permissions_list' => [
            'title' => 'Permisos del Sistema',
            'route_name_suffix' => 'permissions.index',
        ],
    ],

    // Configuración de breadcrumbs para cada ruta
    'breadcrumbs' => [
        'default' => [
            $adminRootRef,
        ],
        'users.index' => [
            $adminRootRef,
            $usersListRef,
        ],
        'users.create' => [
            $adminRootRef,
            $usersListRef,
            '$ref:breadcrumb_components.users_create',
        ],
        'users.edit' => [
            $adminRootRef,
            $usersListRef,
            '$ref:breadcrumb_components.users_edit',
        ],
        'roles.index' => [
            $adminRootRef,
            '$ref:breadcrumb_components.roles_list',
        ],
        'roles.create' => [
            $adminRootRef,
            '$ref:breadcrumb_components.roles_list',
            '$ref:breadcrumb_components.roles_create',
        ],
        'roles.edit' => [
            $adminRootRef,
            '$ref:breadcrumb_components.roles_list',
            '$ref:breadcrumb_components.roles_edit',
        ],
        'permissions.index' => [
            $adminRootRef,
            '$ref:breadcrumb_components.permissions_list',
        ],
    ],
];
