<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tighten\Ziggy\Ziggy;

/**
 * Servicio para filtrar rutas de Ziggy según el tipo de usuario
 */
final class RouteFilterService
{
    /**
     * Obtener rutas de Ziggy filtradas para el contexto actual
     */
    public function getFilteredZiggy(Request $request): array
    {
        // Obtener todas las rutas disponibles
        $ziggy = new Ziggy;
        $allRoutes = $ziggy->toArray();
        $routes = $allRoutes['routes'];

        // Aplicar filtro según el usuario autenticado
        $filteredRoutes = $this->filterRoutesByUserType($routes);

        // Aplicar exclusiones específicas basadas en el contexto
        $filteredRoutes = $this->applySpecificExclusions($filteredRoutes, $request);

        // Retornar la estructura de Ziggy con rutas filtradas
        return [
            'url' => $allRoutes['url'],
            'port' => $allRoutes['port'],
            'defaults' => $allRoutes['defaults'],
            'routes' => $filteredRoutes,
            'location' => $request->url(),
        ];
    }

    /**
     * Aplica exclusiones específicas basadas en el contexto actual
     */
    private function applySpecificExclusions(
        array $routes,
        Request $request
    ): array {
        // Si estamos en la página de bienvenida, ser muy restrictivos
        if ($request->path() === '/' || $request->path() === '') {
            // Estos son los únicos prefijos que realmente necesitamos en la página de bienvenida
            $allowedPrefixes = [
                'sanctum.csrf-cookie',
            ];

            return array_filter($routes, function ($key) use ($allowedPrefixes) {
                foreach ($allowedPrefixes as $prefix) {
                    if (mb_strpos($key, $prefix) === 0) {
                        return true;
                    }
                }

                return false;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $routes;
    }

    /**
     * Filtrar rutas según el tipo de usuario actual
     */
    private function filterRoutesByUserType(array $routes): array
    {
        // Determinar patrones a utilizar según el usuario actual
        $patterns = $this->getPatternsByUserType();

        // Filtrar rutas según patrones
        return $this->filterRoutesByPatterns($routes, $patterns);
    }

    /**
     * Obtener los patrones de rutas aplicables según el tipo de usuario actual
     */
    private function getPatternsByUserType(): array
    {
        // Cargar patrones desde la configuración
        $publicPatterns = Config::get('routes.filters.public', []);
        $staffPatterns = Config::get('routes.filters.staff', []);

        // Por defecto, solo patrones públicos
        $patterns = $publicPatterns;

        // Si es staff, añadir patrones de staff
        if (Auth::guard('staff')->check()) {
            $patterns = array_merge($patterns, $staffPatterns);
        }

        return $patterns;
    }

    /**
     * Filtrar rutas basado en un conjunto de patrones
     */
    private function filterRoutesByPatterns(array $routes, array $patterns): array
    {
        $filteredRoutes = [];
        $notMatchedRoutes = [];

        // Primero procesamos las rutas que coincidan exactamente
        foreach ($routes as $name => $route) {
            // Si la ruta está exactamente en los patrones, añadirla directamente
            if (in_array($name, $patterns)) {
                $filteredRoutes[$name] = $route;

                continue;
            }

            $notMatchedRoutes[$name] = $route;
        }

        // Luego procesamos las rutas que coincidan con patrones con comodines
        $wildcardPatterns = array_filter($patterns, function ($pattern) {
            return mb_strpos($pattern, '*') !== false;
        });

        if (count($wildcardPatterns) > 0) {
            foreach ($notMatchedRoutes as $name => $route) {
                foreach ($wildcardPatterns as $pattern) {
                    // Convertir el patrón a expresión regular
                    $regexPattern = $this->patternToRegex($pattern);

                    // Verificar si la ruta coincide con el patrón
                    if (preg_match($regexPattern, $name)) {
                        $filteredRoutes[$name] = $route;
                        break;
                    }
                }
            }
        }

        // Si estamos en modo debug, loguear información útil
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::debug('Ziggy route filtering', [
                'patterns' => $patterns,
                'total_routes' => count($routes),
                'filtered_routes' => count($filteredRoutes),
                'route_names' => array_keys($filteredRoutes),
            ]);
        }

        return $filteredRoutes;
    }

    /**
     * Verificar si una ruta coincide con alguno de los patrones
     */
    private function routeMatchesPatterns(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Convertir el patrón a expresión regular
            $regexPattern = $this->patternToRegex($pattern);

            // Verificar si la ruta coincide con el patrón
            if (preg_match($regexPattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convertir un patrón con wildcards a expresión regular
     */
    private function patternToRegex(string $pattern): string
    {
        // Escapar caracteres especiales de regex, excepto asteriscos
        $pattern = preg_quote($pattern, '/');

        // Reemplazar asteriscos con el patrón adecuado
        $pattern = str_replace('\*', '.*', $pattern);

        // Crear regex completa
        return '/^'.$pattern.'$/';
    }
}
