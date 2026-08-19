<?php

declare(strict_types=1);

namespace Modules\Core\Application\Permissions;

use Modules\Core\Domain\Permission\PermissionCollection;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

/**
 * Caso de uso: obtener permisos del usuario en todos los guards.
 */
final readonly class GetUserPermissions
{
    /**
     * Devuelve la colección de permisos del usuario staff (cross-guard).
     */
    public function handle(AbstractDomainUser $user): PermissionCollection
    {
        return PermissionCollection::fromArray(
            $user->getAllCrossGuardPermissions()
        );
    }
}
