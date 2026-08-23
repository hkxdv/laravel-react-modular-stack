<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para el mapa de breadcrumbs.
 *
 * Claveado por sufijo de ruta, cada entrada contiene una lista de BreadcrumbItem.
 */
#[TypeScript]
final readonly class BreadcrumbMap
{
    /**
     * @param  array<string, list<BreadcrumbItem>>  $items  Items claveados por sufijo de ruta.
     */
    public function __construct(
        public array $items,
    ) {
        //
    }

    /**
     * Fábrica: construye un BreadcrumbMap vacío.
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Fábrica: construye un BreadcrumbMap desde arrays de configuración con resolución de referencias.
     *
     * Cada clave string en $breadcrumbsArray se resuelve contra $componentsArray → BreadcrumbItem.
     *
     * @param  array<string, list<string>>  $breadcrumbsArray  Breadcrumbs claveados por sufijo de ruta.
     * @param  array<string, array<string, mixed>>  $componentsArray  Definiciones de componentes breadcrumb.
     *
     * @throws InvalidAddonConfig Si una clave de referencia no se encuentra en $componentsArray.
     */
    public static function fromConfigArray(array $breadcrumbsArray, array $componentsArray): self
    {
        /** @var array<string, list<BreadcrumbItem>> $items */
        $items = [];

        foreach ($breadcrumbsArray as $routeSuffix => $componentKeys) {
            $crumbs = [];
            foreach ($componentKeys as $key) {
                throw_if(
                    ! isset($componentsArray[$key]),
                    InvalidAddonConfig::class,
                    sprintf("BreadcrumbMap: component '%s' not found in breadcrumb_components config", $key)
                );

                $crumbs[] = BreadcrumbItem::fromConfigArray($componentsArray[$key]);
            }

            $items[$routeSuffix] = $crumbs;
        }

        return new self($items);
    }
}
