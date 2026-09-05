<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\PermissionRegistryInterface;

/**
 * Registra los permisos granulares del módulo Core.
 */
final readonly class CorePermissionRegistry implements PermissionRegistryInterface
{
    public function __construct(
        // El guard por defecto es el del backoffice ('staff'), fijo en el punto
        // de composición (Infrastructure). NO derivar de config('auth.defaults.guard')
        // porque la app usa 'web' por defecto y los permisos sistémicos deben
        // registrarse para el guard administrativo. Un módulo puede pasar otro
        // guard explícito en el constructor (capacidad testeable).
        private string $guard = 'staff',
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        $guardName = $this->guard;

        return [
            [
                'name' => 'system.bypass',
                'description' => 'Acceso total al sistema (ADMIN/DEV)',
                'guard' => $guardName,
            ],
            [
                'name' => 'permissions.sync',
                'description' => 'Sincronizar permisos del sistema',
                'guard' => $guardName,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function moduleName(): string
    {
        return 'Core';
    }
}
