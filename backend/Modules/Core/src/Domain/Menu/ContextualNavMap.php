<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para el mapa de navegación contextual.
 *
 * Claveado por sufijo de ruta, cada entrada contiene una lista de
 * NavComponentLink o NavComponentGroup items.
 */
#[TypeScript]
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
}
