<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem de breadcrumb resuelto tras build.
 *
 * Representa la salida de BuildBreadcrumbs::execute():
 * {title: string, href: string}.
 */
#[TypeScript]
final readonly class ResolvedBreadcrumbItem
{
    public function __construct(
        public string $title,
        public string $href,
    ) {
        //
    }
}
