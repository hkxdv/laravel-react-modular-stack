<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\AccountSecurity;

use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

/**
 * Interfaz para completar el challenge de 2FA durante el login.
 *
 * Verifica un código TOTP o un recovery code del usuario pendiente y
 * establece la sesión autenticada en el guard indicado. NO marca 2FA como
 * confirmado (a diferencia de ConfirmTwoFactorAuthInterface).
 */
interface VerifyLoginChallengeInterface
{
    /**
     * Completa el login de un usuario pendiente de 2FA.
     *
     * @param  AbstractDomainUser  $user  Usuario que inició sesión (credenciales válidas).
     * @param  string  $code  Código TOTP o recovery code.
     * @param  bool  $remember  Reusar la preferencia del formulario de login.
     * @return bool Verdadero si el challenge es válido y la sesión quedó establecida.
     */
    public function handle(
        AbstractDomainUser $user,
        string $code,
        bool $remember = false
    ): bool;
}
