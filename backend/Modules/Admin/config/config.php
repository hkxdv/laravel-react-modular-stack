<?php

declare(strict_types=1);

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
                'title' => 'Lista de Usuarios',
                'route_name_suffix' => 'users.index',
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
                'route_name_suffix' => 'users.index',
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
            'user_management' => [
                'panel',
                'users_list',
                'users_create',
            ],
            'back_navigation' => [
                'back_to_panel',
                'back_to_list',
            ],
        ],
    ],

    // Configuración de navegación contextual
    'contextual_nav' => [
        'default' => [
            'group:user_management',
        ],

        // Rutas para la gestión de usuarios
        'users.index' => [
            'back_to_panel',
            'users_create',
        ],
        'users.create' => [
            'group:back_navigation',
        ],
        'users.edit' => [
            'back_to_panel',
            'back_to_list',
        ],

        // Rutas para la gestión de roles
        'roles.index' => [
            'back_to_panel',
        ],
        'roles.create' => [
            'back_to_panel',
            'roles_list',
        ],
        'roles.edit' => [
            'back_to_panel',
            'roles_list',
        ],

        // Rutas para permisos
        'permissions.index' => [
            'back_to_panel',
        ],
    ],

    // Configuración de ítems del panel
    'panel_items' => [
        [
            'name' => 'Lista de Usuarios',
            'description' => 'Añadir, editar o eliminar cuentas de usuario.',
            'route_name_suffix' => 'users.index',
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
            'title' => 'Módulo de Administración',
            'route_name_suffix' => 'index',
        ],
        'users_list' => [
            'title' => 'Lista de Usuarios',
            'route_name_suffix' => 'users.index',
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
            'admin_root',
        ],
        'users.index' => [
            'admin_root',
            'users_list',
        ],
        'users.create' => [
            'admin_root',
            'users_list',
            'users_create',
        ],
        'users.edit' => [
            'admin_root',
            'users_list',
            'users_edit',
        ],
        'roles.index' => [
            'admin_root',
            'roles_list',
        ],
        'roles.create' => [
            'admin_root',
            'roles_list',
            'roles_create',
        ],
        'roles.edit' => [
            'admin_root',
            'roles_list',
            'roles_edit',
        ],
        'permissions.index' => [
            'admin_root',
            'permissions_list',
        ],
    ],
];
