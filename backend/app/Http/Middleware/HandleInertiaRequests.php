<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Resources\StaffUserResource;
use App\Models\StaffUsers;
use App\Services\ModuleRegistryService;
use App\Services\NavigationBuilderService;
use App\Services\RouteFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * La plantilla raíz que se carga en la primera visita a la página.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determina la versión actual de los assets.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define las props que se comparten por defecto con todas las vistas de Inertia.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedData = parent::share($request);

        /** @var \App\Models\StaffUsers|null $staffUser */
        $staffUser = Auth::guard('staff')->user();

        // Si hay un usuario de staff, construir y añadir sus datos de navegación.
        if ($staffUser) {
            $moduleRegistry = app(ModuleRegistryService::class);
            $navBuilder = app(NavigationBuilderService::class);
            $permissionChecker = fn(string $permission) => $staffUser->hasPermissionToCross($permission);

            // Construir items de navegación contextual (módulos)
            $modules = $moduleRegistry->getAvailableModulesForUser($staffUser);
            $sharedData['contextualNavItems'] = $navBuilder->buildNavItems($modules, $permissionChecker);

            // Construir items de navegación global (configuración)
            $globalItemsConfig = $moduleRegistry->getGlobalNavItems($staffUser);
            $sharedData['globalNavItems'] = $navBuilder->buildGlobalNavItems($globalItemsConfig, $permissionChecker);
        } else {
            // Asegurarse de que las props siempre existan para el frontend
            $sharedData['contextualNavItems'] = [];
            $sharedData['globalNavItems'] = [];
        }

        return array_merge($sharedData, [
            'name' => config('app.name', 'Laravel'),
            'auth' => function () use ($request) {
                $staffUser = $request->user('staff');

                $transformUser = function ($user) {
                    if ($user instanceof StaffUsers) {
                        return new StaffUserResource($user);
                    }
                    return null;
                };

                $transformedStaffUser = $transformUser($staffUser);

                $user = $staffUser;

                return [
                    'user' => $transformedStaffUser,
                    'staff' => $transformedStaffUser,
                    'can' => $user ? $user->getFrontendPermissionsAttribute() : [],
                    'impersonate' => $user && session()->has('impersonated_by'),
                ];
            },
            'ziggy' => function () use ($request) {
                // Utilizar el servicio de filtrado de rutas para obtener las rutas
                // según el tipo de usuario actual
                return app(RouteFilterService::class)->getFilteredZiggy($request);
            },
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'info' => fn() => $request->session()->get('info'),
                'warning' => fn() => $request->session()->get('warning'),
                'credentials' => fn() => $request->session()->get('credentials'),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ]);
    }
}
