<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

/**
 * DTO inmutable para el mapa de navegación contextual.
 *
 * Claveado por sufijo de ruta, cada entrada contiene una lista de
 * NavComponentLink o NavComponentGroup items.
 */
final readonly class ContextualNavMap
{
    /**
     * @param  array<string, list<NavComponentLink|NavComponentGroup>>  $items  Items claveados por sufijo de ruta.
     */
    public function __construct(
        public array $items,
    ) {
        //
    }

    /**
     * Fábrica: construye un ContextualNavMap desde un array de items.
     *
     * @param  array<string, list<NavComponentLink|NavComponentGroup>>  $items
     */
    public static function of(array $items): self
    {
        return new self($items);
    }

    /**
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return array<string, list<array>>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->items as $suffix => $entries) {
            $result[$suffix] = array_map(
                static fn (NavComponentLink|NavComponentGroup $item): array => $item->toArray(),
                $entries,
            );
        }

        return $result;
    }
}
