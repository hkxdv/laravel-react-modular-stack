<?php

declare(strict_types=1);

return [
    // Configuración básica del módulo
    'functional_name' => 'Módulo de ejemplos (Tenant)',
    'description' => 'Módulo esquelético que valida el soporte multi-usuario con guard tenant.',
    'module_slug' => 'examples',
    'inertia_view_directory' => 'examples',
    'base_permission' => 'examples.dashboard.access',

    // Configuración del ítem de navegación principal
    'nav_item' => [
        'show_in_nav' => true,
        'route_name' => 'internal.tenant.examples.index',
        'icon' => 'ClipboardList',
    ],

    // Componentes reutilizables de navegación
    'nav_components' => [
        'links' => [
            'example_panel' => [
                'title' => 'Panel de ejemplo',
                'route_name_suffix' => 'index',
                'icon' => 'LayoutDashboard',
                'permission' => 'examples.dashboard.access',
            ],
        ],
    ],

    // Configuración de navegación contextual (mínima)
    'contextual_nav' => [
        'default' => [
            'example_panel',
        ],
    ],

    // Configuración de ítems del panel (mínima)
    'panel_items' => [
        [
            'name' => 'Item de ejemplo 1',
            'description' => 'Item de ejemplo 1 para la demostración del proyecto.',
            'route_name_suffix' => 'index',
            'icon' => 'FilePlus2',
            'permission' => 'examples.dashboard.access',
        ],
    ],
];
