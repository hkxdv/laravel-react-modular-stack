<?php

declare(strict_types=1);

return [
    'functional_name' => 'Core',
    'description' => 'Funciones transversales del área interna',
    'module_slug' => 'core',
    'auth_guard' => 'staff',
    'inertia_view_directory' => 'core',
    'base_permission' => null,

    'guards' => [
        'staff' => [
            'login_route' => 'login',
            'redirect_route' => 'login',
            'provider' => 'staff',
            'two_factor_required' => env('STAFF_2FA_REQUIRED', false), // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig (config de módulo; env legítimo en config)
        ],
        'tenant' => [
            'login_route' => 'tenant.login',
            'redirect_route' => 'tenant.login',
            'provider' => 'tenant',
            'two_factor_required' => false,
        ],
        'web' => [
            'login_route' => 'login',
            'redirect_route' => 'welcome',
            'provider' => 'web',
            'two_factor_required' => false,
        ],
        'sanctum' => [
            'login_route' => 'login',
            'redirect_route' => 'welcome',
            'provider' => 'sanctum',
        ],
    ],

    'sync_excludes' => ['staff'],

    'cache' => [
        'nav_cache_prefix' => 'core:nav:',
        'nav_version_key' => 'core.nav_version',
        'modules_statuses_mtime_key' => 'core.modules_statuses_mtime',
        'nav_assembled_ttl_seconds' => 300,
        'breadcrumbs_ttl_seconds' => 300,
        'global_nav_items_ttl_seconds' => 300,
    ],

    'nav_components' => [
        'links' => [
            'dashboard' => [
                'title' => 'Dashboard',
                'route_name_suffix' => 'internal.staff.dashboard',
                'icon' => 'LayoutDashboard',
                'permission' => null,
            ],
            'profile' => [
                'title' => 'Perfil',
                'route_name_suffix' => 'internal.staff.profile.edit',
                'icon' => 'UserCog',
                'permission' => null,
            ],
            'password' => [
                'title' => 'Contraseña',
                'route_name_suffix' => 'internal.staff.password.edit',
                'icon' => 'KeyRound',
                'permission' => null,
            ],
            'appearance' => [
                'title' => 'Apariencia',
                'route_name_suffix' => 'internal.staff.appearance',
                'icon' => 'Palette',
                'permission' => null,
            ],
            'account_security' => [
                'title' => 'Seguridad',
                'route_name_suffix' => 'internal.staff.security.edit',
                'icon' => 'Shield',
                'permission' => null,
            ],
            'notification_preferences' => [
                'title' => 'Notificaciones',
                'route_name_suffix' => 'internal.staff.notifications.edit',
                'icon' => 'Bell',
                'permission' => null,
            ],
        ],

        'groups' => [
            'user_profile_nav' => [
                'profile',
                'password',
                'appearance',
                'account_security',
                'notification_preferences',
            ],
        ],
    ],

    'contextual_nav' => [
        'default' => [
            'group:user_profile_nav',
        ],
    ],

    'breadcrumb_components' => [
        'user_profile_root' => [
            'title' => 'Configuración',
            'route_name_suffix' => 'internal.staff.profile.edit',
        ],
        'user_profile_profile' => [
            'title' => 'Perfil',
            'route_name_suffix' => 'internal.staff.profile.edit',
        ],
        'user_profile_password' => [
            'title' => 'Contraseña',
            'route_name_suffix' => 'internal.staff.password.edit',
        ],
        'user_profile_appearance' => [
            'title' => 'Apariencia',
            'route_name_suffix' => 'internal.staff.appearance',
        ],
        'user_profile_security' => [
            'title' => 'Seguridad',
            'route_name_suffix' => 'internal.staff.security.edit',
        ],
        'user_profile_notifications' => [
            'title' => 'Notificaciones',
            'route_name_suffix' => 'internal.staff.notifications.edit',
        ],
    ],
    'breadcrumbs' => [
        'profile.edit' => [
            'user_profile_root',
            'user_profile_profile',
        ],
        'password.edit' => [
            'user_profile_root',
            'user_profile_password',
        ],
        'appearance' => [
            'user_profile_root',
            'user_profile_appearance',
        ],
        'security.edit' => [
            'user_profile_root',
            'user_profile_security',
        ],
        'notifications.edit' => [
            'user_profile_root',
            'user_profile_notifications',
        ],
    ],

    'module-config' => [
        'frontend_path' => 'frontend/src/pages/modules',
    ],
];
