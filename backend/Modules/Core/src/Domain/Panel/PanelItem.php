<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Panel;

use Modules\Core\Domain\Addon\InvalidAddonConfig;

/**
 * DTO inmutable para un ítem del panel de un módulo.
 *
 * Valida en constructor que name y routeNameSuffix no estén vacíos.
 * toArray() preserva la key shape exacta del config actual.
 */
final readonly class PanelItem
{
    /**
     * @param  string  $name  Nombre del ítem del panel.
     * @param  string  $description  Descripción del ítem.
     * @param  string  $routeNameSuffix  Sufijo de la ruta.
     * @param  string  $icon  Icono del ítem.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
    ) {
        throw_if(
            $this->name === '',
            InvalidAddonConfig::class,
            'PanelItem requires non-empty name'
        );

        throw_if(
            $this->routeNameSuffix === '',
            InvalidAddonConfig::class,
            'PanelItem requires non-empty routeNameSuffix'
        );
    }

    /**
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array{name: string, description: string, route_name_suffix: string, icon: string, permission: string|null}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'route_name_suffix' => $this->routeNameSuffix,
            'icon' => $this->icon,
            'permission' => $this->permission,
        ];
    }
}
