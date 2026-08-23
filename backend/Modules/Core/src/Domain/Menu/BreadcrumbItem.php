<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Menu;

use Modules\Core\Domain\Addon\InvalidAddonConfig;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * DTO inmutable para un ítem de breadcrumb.
 *
 * Valida en constructor que title no esté vacío.
 */
#[TypeScript]
final readonly class BreadcrumbItem
{
    /**
     * @param  string  $title  Título del breadcrumb.
     * @param  string  $routeNameSuffix  Sufijo de la ruta.
     * @param  string|null  $dynamicTitleProp  Propiedad dinámica para el título (ej. 'user.name').
     */
    public function __construct(
        public string $title,
        public string $routeNameSuffix,
        public ?string $dynamicTitleProp = null,
    ) {
        throw_if(
            $this->title === '',
            InvalidAddonConfig::class,
            'BreadcrumbItem requires non-empty title'
        );
    }
}
