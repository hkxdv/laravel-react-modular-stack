<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable que envuelve la salida de ViewComposerService::composeModuleViewContext().
 *
 * Reemplaza el array plano devuelto por el servicio con un DTO tipado.
 */
#[TypeScript]
final readonly class ModulePageProps
{
    /**
     * @param  list<ResolvedPanelItem>  $panelItems
     * @param  list<ResolvedNavItem>  $mainNavItems
     * @param  list<ResolvedNavItem>  $moduleNavItems
     * @param  list<ResolvedNavItem>  $contextualNavItems
     * @param  list<ResolvedNavItem>  $globalNavItems
     * @param  list<ResolvedBreadcrumbItem>  $breadcrumbs
     * @param  array<int, mixed>  $stats
     * @param  array<string, mixed>  $flash
     */
    public function __construct(
        public array $panelItems,
        public array $mainNavItems,
        public array $moduleNavItems,
        public array $contextualNavItems,
        public array $globalNavItems,
        public array $breadcrumbs,
        public array $stats,
        public string $pageTitle,
        public ?string $description,
        public array $flash,
    ) {
        //
    }
}
