<?php

declare(strict_types=1);

namespace Modules\Core\Application\View;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable que envuelve la salida completa de ComposeInertiaProps::execute().
 *
 * Agrega AuthPageProps y SecurityPageProps como DTOs tipados en lugar de arrays.
 */
#[TypeScript]
final readonly class GlobalPageProps
{
    /**
     * @param  array<int, array<string, mixed>>  $breadcrumbs
     * @param  array<int, array<string, mixed>>  $mainNavItems
     * @param  array<int, array<string, mixed>>  $moduleNavItems
     * @param  array<int, array<string, mixed>>  $contextualNavItems
     * @param  array<int, array<string, mixed>>  $globalNavItems
     * @param  array<string, mixed>  $notificationPreferences
     */
    public function __construct(
        public array $breadcrumbs,
        public array $mainNavItems,
        public array $moduleNavItems,
        public array $contextualNavItems,
        public array $globalNavItems,
        public bool $passwordChangeRequired,
        public AuthPageProps $auth,
        public SecurityPageProps $security,
        public array $notificationPreferences,
    ) {
        //
    }
}
