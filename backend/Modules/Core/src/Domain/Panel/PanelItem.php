<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Panel;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem del panel de un módulo.
 *
 * Valida en constructor que name y routeNameSuffix no estén vacíos.
 */
#[TypeScript]
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
     * Fábrica: construye una lista de PanelItem desde un array de configuración.
     *
     * @param  list<array<string, mixed>>  $items  Lista de arrays de configuración.
     * @return list<self> Lista de PanelItem validados.
     *
     * @throws InvalidAddonConfig Si falta name o route_name_suffix en algún ítem.
     */
    public static function fromConfigArray(array $items): array
    {
        /** @var list<self> $result */
        $result = [];

        foreach ($items as $index => $item) {
            $rawName = $item['name'] ?? '';
            $rawDescription = $item['description'] ?? '';
            $rawRouteNameSuffix = $item['route_name_suffix'] ?? '';
            $rawIcon = $item['icon'] ?? '';
            $rawPermission = $item['permission'] ?? null;

            $name = is_string($rawName) ? $rawName : '';
            $description = is_string($rawDescription) ? $rawDescription : '';
            $routeNameSuffix = is_string($rawRouteNameSuffix) ? $rawRouteNameSuffix : '';
            $icon = is_string($rawIcon) ? $rawIcon : '';
            $permission = is_string($rawPermission) && $rawPermission !== '' ? $rawPermission : null;

            throw_if(
                $name === '',
                InvalidAddonConfig::class,
                sprintf('PanelItem [%d] requires non-empty name', $index)
            );

            throw_if(
                $routeNameSuffix === '',
                InvalidAddonConfig::class,
                sprintf('PanelItem [%d] requires non-empty route_name_suffix', $index)
            );

            $result[] = new self(
                name: $name,
                description: $description,
                routeNameSuffix: $routeNameSuffix,
                icon: $icon,
                permission: $permission,
            );
        }

        return $result;
    }
}
