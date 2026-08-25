<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Core\Contracts\MenuBuilderInterface;
use Modules\Core\Contracts\ModuleOrchestratorInterface;

/**
 * Controlador base para controladores del Módulo Admin.
 * Centraliza dependencias vía Contracts y delega la orquestación de vistas.
 */
abstract class AbstractAdminController extends Controller
{
    /**
     * Slug del módulo (configurable en el módulo).
     */
    protected string $moduleSlug;

    /**
     * Inyecta orquestación de vistas, navegación, gestión de usuarios y roles vía Contracts.
     *
     * @param  ModuleOrchestratorInterface  $orchestrator  {@inheritDoc}
     * @param  MenuBuilderInterface  $navigationBuilder  {@inheritDoc}
     * @param  StaffUserManagerInterface  $staffUserManager  {@inheritDoc}
     * @param  RolesInterface  $rolesInterface  Interface para gestión de roles
     */
    public function __construct(
        protected readonly ModuleOrchestratorInterface $orchestrator,
        protected readonly MenuBuilderInterface $navigationBuilder,
        protected readonly StaffUserManagerInterface $staffUserManager,
        protected readonly RolesInterface $rolesInterface,
    ) {
        $this->moduleSlug = $this->resolveModuleSlug();
    }

    /**
     * Devuelve el slug del módulo en minúsculas.
     *
     * @return string Slug del módulo (por ejemplo: 'admin').
     */
    protected function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    /**
     * Resuelve el slug del módulo desde la configuración o un valor predeterminado.
     */
    private function resolveModuleSlug(): string
    {
        $configured = config('admin.module_slug');

        return is_string($configured) && $configured !== ''
            ? $configured
            : 'admin';
    }
}
