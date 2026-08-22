<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

/**
 * DTO inmutable para el mapa de breadcrumbs.
 *
 * Claveado por sufijo de ruta, cada entrada contiene una lista de BreadcrumbItem.
 */
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
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array<string, list<array>>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->items as $suffix => $crumbs) {
            $result[$suffix] = array_map(
                static fn (BreadcrumbItem $crumb): array => $crumb->toArray(),
                $crumbs,
            );
        }

        return $result;
    }
}
