<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un enlace reutilizable de navegación contextual.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 */
#[TypeScript]
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
     * Fábrica: construye un NavComponentLink desde un array de configuración.
     *
     * @param  string  $key  Identificador único del enlace.
     * @param  array<string, mixed>  $data  Array de configuración con title, route_name_suffix, icon (requeridos) y permission (opcional).
     *
     * @throws InvalidAddonConfig Si faltan claves requeridas o route_name_suffix está vacío.
     */
    public static function fromConfigArray(string $key, array $data): self
    {
        $rawTitle = $data['title'] ?? '';
        $rawRouteNameSuffix = $data['route_name_suffix'] ?? '';
        $rawIcon = $data['icon'] ?? '';
        $rawPermission = $data['permission'] ?? null;

        $title = is_string($rawTitle) ? $rawTitle : '';
        $routeNameSuffix = is_string($rawRouteNameSuffix) ? $rawRouteNameSuffix : '';
        $icon = is_string($rawIcon) ? $rawIcon : '';
        $permission = is_string($rawPermission) && $rawPermission !== '' ? $rawPermission : null;

        throw_if(
            $title === '',
            InvalidAddonConfig::class,
            sprintf("NavComponentLink '%s' requires non-empty title", $key)
        );

        throw_if(
            $routeNameSuffix === '',
            InvalidAddonConfig::class,
            sprintf("NavComponentLink '%s' requires non-empty route_name_suffix", $key)
        );

        throw_if(
            $icon === '',
            InvalidAddonConfig::class,
            sprintf("NavComponentLink '%s' requires non-empty icon", $key)
        );

        return new self(
            key: $key,
            title: $title,
            routeNameSuffix: $routeNameSuffix,
            icon: $icon,
            permission: $permission,
        );
    }
}
