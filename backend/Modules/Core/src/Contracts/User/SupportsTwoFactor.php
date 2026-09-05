<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\User;

use DateTimeImmutable;

/**
 * Contrato de capacidad: el usuario soporta 2FA (TOTP).
 *
 * Expone los accesores de 2FA para que las capas de aplicación decidan
 * mediante capacidades, sin conocer las columnas del modelo concreto
 * ni el guard literal.
 */
interface SupportsTwoFactor
{
    /**
     * Obtiene el secreto TOTP almacenado (cifrado en reposo).
     *
     * @return string|null El secreto base32 cifrado, o null si no hay 2FA iniciado.
     */
    public function twoFactorSecret(): ?string;

    /**
     * Obtiene la fecha de confirmación de 2FA.
     *
     * @return DateTimeImmutable|null Fecha de confirmación, o null si no está confirmado.
     */
    public function twoFactorConfirmedAt(): ?DateTimeImmutable;

    /**
     * Indica si el 2FA está confirmado y activo.
     */
    public function twoFactorEnabled(): bool;

    /**
     * Indica si el 2FA está pendiente de confirmación (secreto presente, sin confirmar).
     */
    public function twoFactorPending(): bool;
}
