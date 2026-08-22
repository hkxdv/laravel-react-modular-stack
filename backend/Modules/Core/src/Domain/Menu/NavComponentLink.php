<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;

/**
 * DTO inmutable para un enlace reutilizable de navegación contextual.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 * toArray() preserva la key shape exacta del config actual.
 */
final readonly class NavComponentLink
{
    /**
     * @param  string  $key  Identificador único del enlace.
     * @param  string  $title  Título del enlace.
     * @param  string  $routeNameSuffix  Sufijo de la ruta.
     * @param  string  $icon  Icono del enlace.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
    ) {
        throw_if(
            $this->routeNameSuffix === '',
            InvalidAddonConfig::class,
            'NavComponentLink requires non-empty routeNameSuffix'
        );
    }

    /**
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array{title: string, route_name_suffix: string, icon: string, permission: string|null}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'route_name_suffix' => $this->routeNameSuffix,
            'icon' => $this->icon,
            'permission' => $this->permission,
        ];
    }
}
