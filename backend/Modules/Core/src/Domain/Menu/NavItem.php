<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para el ítem de navegación principal de un módulo.
 *
 * Valida en constructor que routeNameSuffix no esté vacío.
 */
#[TypeScript]
final readonly class NavItem
{
    /**
     * @param  string  $title  Título del ítem de navegación.
     * @param  string  $routeNameSuffix  Sufijo o nombre completo de la ruta.
     * @param  string  $icon  Icono del ítem.
     * @param  string|null  $permission  Permiso requerido (null si no requiere).
     * @param  bool  $showInNav  Si se muestra en la navegación.
     * @param  bool  $showInMainNav  Si se muestra en la barra lateral principal.
     */
    public function __construct(
        public string $title,
        public string $routeNameSuffix,
        public string $icon,
        public ?string $permission = null,
        public bool $showInNav = true,
        public bool $showInMainNav = false,
    ) {
        throw_if(
            $this->routeNameSuffix === '',
            InvalidAddonConfig::class,
            'NavItem requires non-empty routeNameSuffix'
        );
    }

    /**
     * Fábrica: construye un NavItem marcado para mostrar en navegación.
     */
    public static function show(string $routeName, string $icon, ?string $permission = null): self
    {
        return new self(
            title: '',
            routeNameSuffix: $routeName,
            icon: $icon,
            permission: $permission,
            showInNav: true,
        );
    }
}
