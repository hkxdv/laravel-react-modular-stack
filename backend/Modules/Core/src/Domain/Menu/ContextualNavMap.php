<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
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

    /**
     * Fábrica: construye un ContextualNavMap desde arrays de configuración con resolución de referencias.
     *
     * Claves con prefijo `group:` se resuelven contra $groupsArray → NavComponentGroup.
     * Claves simples se resuelven contra $linksArray → NavComponentLink.
     *
     * @param  array<string, list<string>>  $navArray  Navegación claveada por sufijo de ruta.
     * @param  array<string, array<string, mixed>>  $linksArray  Definiciones de enlaces.
     * @param  array<string, list<string>>  $groupsArray  Definiciones de grupos (listas de claves de enlace).
     *
     * @throws InvalidAddonConfig Si una clave de referencia no se encuentra en el mapa correspondiente.
     */
    public static function fromConfigArray(array $navArray, array $linksArray, array $groupsArray): self
    {
        /** @var array<string, list<NavComponentLink|NavComponentGroup>> $items */
        $items = [];

        foreach ($navArray as $routeSuffix => $entries) {
            $resolved = [];
            foreach ($entries as $entry) {
                if (str_starts_with($entry, 'group:')) {
                    $groupName = mb_substr($entry, 6);

                    throw_if(
                        ! isset($groupsArray[$groupName]),
                        InvalidAddonConfig::class,
                        sprintf("ContextualNavMap: group '%s' not found in groups config", $groupName)
                    );

                    $groupLinks = [];
                    foreach ($groupsArray[$groupName] as $linkKey) {
                        throw_if(
                            ! isset($linksArray[$linkKey]),
                            InvalidAddonConfig::class,
                            sprintf("ContextualNavMap: link '%s' in group '%s' not found in links config", $linkKey, $groupName)
                        );

                        $groupLinks[] = NavComponentLink::fromConfigArray($linkKey, $linksArray[$linkKey]);
                    }

                    $resolved[] = new NavComponentGroup(name: $groupName, links: $groupLinks);
                } else {
                    throw_if(
                        ! isset($linksArray[$entry]),
                        InvalidAddonConfig::class,
                        sprintf("ContextualNavMap: link '%s' not found in links config", $entry)
                    );

                    $resolved[] = NavComponentLink::fromConfigArray($entry, $linksArray[$entry]);
                }
            }

            $items[$routeSuffix] = $resolved;
        }

        return new self($items);
    }
}
