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
     * @param  array<int, array<string, mixed>>  $panelItems
     * @param  array<int, array<string, mixed>>  $mainNavItems
     * @param  array<int, array<string, mixed>>  $moduleNavItems
     * @param  array<int, array<string, mixed>>  $contextualNavItems
     * @param  array<int, array<string, mixed>>  $globalNavItems
     * @param  array<int, array<string, mixed>>  $breadcrumbs
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
