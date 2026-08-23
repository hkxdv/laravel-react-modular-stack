<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para el ítem de navegación principal de un módulo.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 */
#[TypeScript]
final readonly class NavItem
{
    /**
     * @param  string  $title  Título del ítem de navegación.
     * @param  string  $routeNameSuffix  Sufijo o nombre completo de la ruta.
     * @param  string  $icon  Icono del ítem.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     * @param  bool  $showInNav  Si se muestra en la navegación.
     * @param  bool  $showInMainNav  Si se muestra en la barra lateral principal.
     */
    public function __construct(
        public string $title,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
        public bool $showInNav = true,
        public bool $showInMainNav = false,
    ) {
        throw_if(
            $this->routeNameSuffix === '',
            InvalidAddonConfig::class,
            'NavItem requires non-empty routeNameSuffix'
        );
    }

    /**
     * Fábrica: construye un NavItem marcado para mostrar en navegación.
     */
    public static function show(string $routeName, string $icon, ?string $permission = null): self
    {
        return new self(
            title: '',
            routeNameSuffix: $routeName,
            icon: $icon,
            permission: $permission,
            showInNav: true,
        );
    }

    /**
     * Fábrica: construye un NavItem desde un array de configuración.
     *
     * @param  array<string, mixed>  $data  Array de configuración con route_name (requerido).
     * @param  string  $fallbackTitle  Título de respaldo cuando title está vacío o ausente.
     *
     * @throws InvalidAddonConfig Si route_name falta o está vacío.
     */
    public static function fromConfigArray(array $data, string $fallbackTitle = ''): self
    {
        $rawRouteName = $data['route_name'] ?? '';
        $rawTitle = $data['title'] ?? '';
        $rawIcon = $data['icon'] ?? '';
        $rawPermission = $data['permission'] ?? null;
        $rawShowInNav = $data['show_in_nav'] ?? true;
        $rawShowInMainNav = $data['show_in_main_nav'] ?? false;

        $routeName = is_string($rawRouteName) ? $rawRouteName : '';
        $title = is_string($rawTitle) ? $rawTitle : '';
        $icon = is_string($rawIcon) ? $rawIcon : '';
        $permission = is_string($rawPermission) && $rawPermission !== '' ? $rawPermission : null;
        $showInNav = is_bool($rawShowInNav) ? $rawShowInNav : true;
        $showInMainNav = is_bool($rawShowInMainNav) && $rawShowInMainNav;

        throw_if(
            $routeName === '',
            InvalidAddonConfig::class,
            'NavItem fromConfigArray requires non-empty route_name'
        );

        return new self(
            title: $title !== '' ? $title : $fallbackTitle,
            routeNameSuffix: $routeName,
            icon: $icon,
            permission: $permission,
            showInNav: $showInNav,
            showInMainNav: $showInMainNav,
        );
    }
}
