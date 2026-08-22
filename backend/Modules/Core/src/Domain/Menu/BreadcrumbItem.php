<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;

/**
 * DTO inmutable para un ítem de breadcrumb.
 *
 * Valida en constructor que title no esté vacío.
 * toArray() preserva la key shape exacta del config actual.
 */
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
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array{title: string, route_name: string, dynamic_title_prop?: string}
     */
    public function toArray(): array
    {
        $result = [
            'title' => $this->title,
            'route_name' => $this->routeNameSuffix,
        ];

        if ($this->dynamicTitleProp !== null) {
            $result['dynamic_title_prop'] = $this->dynamicTitleProp;
        }

        return $result;
    }
}
