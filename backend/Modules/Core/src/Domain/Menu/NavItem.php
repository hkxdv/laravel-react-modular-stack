<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;

/**
 * DTO inmutable para el ítem de navegación principal de un módulo.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 * toArray() preserva la key shape exacta del config actual.
 */
final readonly class NavItem
{
    /**
     * @param  string  $title  Título del ítem de navegación.
     * @param  string  $routeNameSuffix  Sufijo o nombre completo de la ruta.
     * @param  string  $icon  Icono del ítem.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     * @param  bool  $showInNav  Si se muestra en la navegación.
     */
    public function __construct(
        public string $title,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
        public bool $showInNav = true,
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
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array{show_in_nav: bool, route_name: string, icon: string, permission: string|null}
     */
    public function toArray(): array
    {
        return [
            'show_in_nav' => $this->showInNav,
            'route_name' => $this->routeNameSuffix,
            'icon' => $this->icon,
            'permission' => $this->permission,
        ];
    }
}
