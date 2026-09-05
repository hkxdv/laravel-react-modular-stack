<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Services;

use Modules\Core\Contracts\PermissionRegistryInterface;

use function Foundry\Helpers\configString;

/**
 * Registra los permisos granulares del módulo Core.
 */
final readonly class CorePermissionRegistry implements PermissionRegistryInterface
{
    public function __construct(
        // D2 (security): sin guard se publica SOLO al guard por defecto de la
        // app (config('auth.defaults.guard')), nunca a todos los core.guards.
        private ?string $guard = null,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function permissions(): array
    {
        $guardName = $this->guard ?? configString('auth.defaults.guard', 'web');

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
