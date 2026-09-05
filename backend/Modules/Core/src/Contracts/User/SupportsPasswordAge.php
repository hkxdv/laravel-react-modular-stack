<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\User;

use DateTimeImmutable;

/**
 * Contrato de capacidad: el usuario expone la antigüedad de su contraseña.
 *
 * Permite a la capa de aplicación verificar políticas de expiración de
 * contraseña sin acoplarse a las columnas del modelo concreto.
 */
interface SupportsPasswordAge
{
    /**
     * Obtiene la fecha del último cambio de contraseña.
     *
     * @return DateTimeImmutable|null Fecha del cambio, o null si nunca se registró.
     */
    public function passwordChangedAt(): ?DateTimeImmutable;
}
