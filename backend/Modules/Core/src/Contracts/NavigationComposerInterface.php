<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use App\Interfaces\AuthenticatableUser;

/**
 * Interfaz para la composición de elementos de navegación con caché versionada.
 */
interface NavigationComposerInterface
{
    /**
     * Compone la estructura completa de navegación aplicando caché versionada.
     *
     * @param  string  $moduleSlug  Slug del módulo
     * @param  array<int, array<string, mixed>>  $contextualNavItemsConfig  Configuración de navegación contextual
     * @param  callable  $permissionChecker  Función para verificar permisos
     * @param  AuthenticatableUser|null  $user  Usuario autenticado
     * @param  string  $functionalName  Nombre funcional del módulo
     * @param  string  $routeSuffix  Sufijo de ruta para breadcrumbs
     * @param  array<string, mixed>  $routeParams  Parámetros de ruta
     * @param  array<string, mixed>  $data  Datos adicionales de la vista
     * @return array<string, mixed>
     */
    public function composeNavigation(
        string $moduleSlug,
        array $contextualNavItemsConfig,
        callable $permissionChecker,
        ?AuthenticatableUser $user,
        string $functionalName,
        string $routeSuffix,
        array $routeParams,
        array $data
    ): array;
}
