<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

/**
 * DTO inmutable para un grupo de enlaces de navegación reutilizables.
 *
 * Contiene una lista de NavComponentLink items.
 */
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

    /**
     * Devuelve el array con la key shape exacta del config actual.
     *
     * @return list<array{title: string, route_name_suffix: string, icon: string, permission: string|null}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (NavComponentLink $link): array => $link->toArray(),
            $this->links,
        );
    }
}
