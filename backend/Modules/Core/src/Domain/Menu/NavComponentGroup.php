<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un grupo de enlaces de navegación reutilizables.
 *
 * Contiene una lista de NavComponentLink items.
 */
#[TypeScript]
final readonly class NavComponentGroup
{
    /**
     * @param  string  $name  Nombre del grupo.
     * @param  list<NavComponentLink>  $links  Enlaces del grupo.
     */
    public function __construct(
        public string $name,
        public array $links,
    ) {
        //
    }
}
