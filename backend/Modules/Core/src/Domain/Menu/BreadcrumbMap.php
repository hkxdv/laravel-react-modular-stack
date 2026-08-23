<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

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
}
