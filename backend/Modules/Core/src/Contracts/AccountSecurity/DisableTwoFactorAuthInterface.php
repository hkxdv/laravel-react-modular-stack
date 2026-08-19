<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

/**
 * Interfaz para desactivar la autenticación de dos factores (2FA).
 *
 * Elimina la configuración de 2FA del usuario y limpia sus códigos de recuperación.
 */
interface DisableTwoFactorAuthInterface
{
    /**
     * Desactiva 2FA para el usuario dado.
     *
     * @param  AbstractDomainUser  $user  Usuario de personal al que se desactiva 2FA.
     */
    public function handle(AbstractDomainUser $user): void;
}
