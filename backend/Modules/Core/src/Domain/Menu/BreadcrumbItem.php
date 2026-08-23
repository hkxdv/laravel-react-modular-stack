<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem de breadcrumb.
 *
 * Valida en constructor que title no esté vacío.
 */
#[TypeScript]
final readonly class BreadcrumbItem
{
    /**
     * @param  string  $title  Título del breadcrumb.
     * @param  string  $routeNameSuffix  Sufijo de la ruta.
     * @param  string|null  $dynamicTitleProp  Propiedad dinámica para el título (ej. 'user.name').
     */
    public function __construct(
        public string $title,
        public string $routeNameSuffix,
        public ?string $dynamicTitleProp = null,
    ) {
        throw_if(
            $this->title === '',
            InvalidAddonConfig::class,
            'BreadcrumbItem requires non-empty title'
        );
    }

    /**
     * Fábrica: construye un BreadcrumbItem desde un array de configuración.
     *
     * @param  array<string, mixed>  $data  Array de configuración con title, route_name_suffix (requeridos) y dynamic_title_prop (opcional).
     *
     * @throws InvalidAddonConfig Si faltan claves requeridas o title está vacío.
     */
    public static function fromConfigArray(array $data): self
    {
        $rawTitle = $data['title'] ?? '';
        $rawRouteNameSuffix = $data['route_name_suffix'] ?? '';
        $rawDynamicTitleProp = $data['dynamic_title_prop'] ?? '';

        $title = is_string($rawTitle) ? $rawTitle : '';
        $routeNameSuffix = is_string($rawRouteNameSuffix) ? $rawRouteNameSuffix : '';
        $dynamicTitleProp = is_string($rawDynamicTitleProp) && $rawDynamicTitleProp !== '' ? $rawDynamicTitleProp : null;

        throw_if(
            $title === '',
            InvalidAddonConfig::class,
            'BreadcrumbItem fromConfigArray requires non-empty title'
        );

        throw_if(
            $routeNameSuffix === '',
            InvalidAddonConfig::class,
            'BreadcrumbItem fromConfigArray requires non-empty route_name_suffix'
        );

        return new self(
            title: $title,
            routeNameSuffix: $routeNameSuffix,
            dynamicTitleProp: $dynamicTitleProp,
        );
    }
}
