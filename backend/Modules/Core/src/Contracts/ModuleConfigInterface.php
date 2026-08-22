<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use Modules\Core\Domain\Addon\AddonConfig;
use Modules\Core\Domain\Menu\BreadcrumbMap;
use Modules\Core\Domain\Menu\ContextualNavMap;
use Modules\Core\Domain\Menu\NavItem;
use Modules\Core\Domain\Panel\PanelItem;

/**
 * Contrato para la configuración declarativa de cada módulo.
 * Cada módulo implementa esta interfaz declarando sus DTOs tipados.
 */
interface ModuleConfigInterface
{
    /**
     * Devuelve la configuración normalizada del addon.
     */
    public function addon(): AddonConfig;

    /**
     * Devuelve el ítem de navegación principal, o null si no se muestra en nav.
     */
    public function navItem(): ?NavItem;

    /**
     * Devuelve la navegación contextual, claveada por sufijo de ruta.
     */
    public function contextualNav(): ContextualNavMap;

    /**
     * Devuelve los breadcrumbs, claveados por sufijo de ruta.
     */
    public function breadcrumbs(): BreadcrumbMap;

    /**
     * Devuelve los ítems del panel.
     *
     * @return list<PanelItem>
     */
    public function panelItems(): array;
}
