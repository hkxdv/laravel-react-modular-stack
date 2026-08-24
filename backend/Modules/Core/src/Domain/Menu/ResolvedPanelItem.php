<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem de panel resuelto tras build contextual.
 *
 * Representa la salida de BuildContextualMenu::execute() con NAV_TYPE_PANEL:
 * {name, icon, permission, route_name (URL resuelta), description}.
 *
 * Nota: route_name es la URL resuelta (no routeNameSuffix como el DTO de config PanelItem).
 */
#[TypeScript]
final readonly class ResolvedPanelItem
{
    /**
     * @param  string|array<int, string>|null  $permission
     */
    public function __construct(
        public string $name,
        public ?string $icon,
        public string|array|null $permission,
        public ?string $route_name,
        public ?string $description,
    ) {
        //
    }
}
