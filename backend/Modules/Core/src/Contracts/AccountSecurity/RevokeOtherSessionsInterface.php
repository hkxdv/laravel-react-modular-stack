<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

/**
 * Interfaz para revocar sesiones activas distintas a la actual.
 *
 * Cierra todas las sesiones del usuario salvo la indicada como actual (si se proporciona).
 */
interface RevokeOtherSessionsInterface
{
    /**
     * Revoca sesiones del usuario distintas a la actual.
     *
     * @param  AbstractDomainUser  $user  Usuario de personal cuyas sesiones serán revocadas.
     * @param  string|null  $currentSessionId  ID de la sesión actual para no revocarla (opcional).
     * @return int Número de sesiones revocadas.
     */
    public function handle(AbstractDomainUser $user, ?string $currentSessionId): int;
}
