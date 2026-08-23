<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un enlace reutilizable de navegación contextual.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 */
#[TypeScript]
final readonly class NavComponentLink
{
    /**
     * @param  string  $key  Identificador único del enlace.
     * @param  string  $title  Título del enlace.
     * @param  string  $routeNameSuffix  Sufijo de la ruta.
     * @param  string  $icon  Icono del enlace.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     */
    public function __construct(
        public string $key,
        public string $title,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
    ) {
        throw_if(
            $this->routeNameSuffix === '',
            InvalidAddonConfig::class,
            'NavComponentLink requires non-empty routeNameSuffix'
        );
    }
}
