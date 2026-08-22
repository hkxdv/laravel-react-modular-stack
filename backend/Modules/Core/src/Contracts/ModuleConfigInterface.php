<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\Domain\Addon\AddonConfig;

/**
 * Contrato para la configuración declarativa de cada módulo.
 * Cada módulo implementa esta interfaz declarando sus arrays actuales.
 */
interface ModuleConfigInterface
{
    /**
     * Devuelve la configuración normalizada del addon.
     */
    public function addon(): AddonConfig;

    /**
     * Devuelve el ítem de navegación principal, o null si no se muestra en nav.
     *
     * @return array<string, mixed>|null
     */
    public function navItem(): ?array;

    /**
     * Devuelve la navegación contextual, claveada por sufijo de ruta.
     *
     * @return array<string, array<int, mixed>>
     */
    public function contextualNav(): array;

    /**
     * Devuelve los breadcrumbs, claveados por sufijo de ruta.
     *
     * @return array<string, array<int, mixed>>
     */
    public function breadcrumbs(): array;

    /**
     * Devuelve los ítems del panel.
     *
     * @return array<int, array{name: string, description: string, route_name_suffix: string, icon: string, permission: string|null}>
     */
    public function panelItems(): array;
}
