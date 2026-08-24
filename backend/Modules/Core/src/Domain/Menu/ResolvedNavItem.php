<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem de navegación resuelto tras build del menú.
 *
 * Representa la salida de BuildAddonMenu::buildNavItems() y buildModuleNavItems():
 * title, href (URL resuelta), icon, current (bool), permission.
 */
#[TypeScript]
final readonly class ResolvedNavItem
{
    /**
     * @param  string|array<int, string>|null  $permission
     */
    public function __construct(
        public string $title,
        public string $href,
        public ?string $icon,
        public bool $current,
        public string|array|null $permission,
    ) {
        //
    }
}
