<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Mappers;

use Modules\Core\Domain\Permission\PermissionCollection;
use Modules\Core\Domain\User\DomainUser;
use Modules\Core\Domain\User\DomainUserId;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;

use function Foundry\Helpers\userId;

/**
 * Mapper de infraestructura para convertir modelos Eloquent a entidades de dominio.
 */
final readonly class DomainUserMapper
{
    /**
     * Convierte un modelo Eloquent de usuario a la entidad de dominio.
     *
     * @param  AbstractDomainUser  $model  Modelo Eloquent
     */
    public static function toDomain(AbstractDomainUser $model): DomainUser
    {
        $idStr = userId($model, '');

        $permissions = PermissionCollection::fromArray(
            $model->getAllCrossGuardPermissions()
        );

        $roles = array_values(array_filter(
            $model->roles->pluck('name')->all(),
            is_string(...)
        ));

        $nameVal = $model->getAttribute('name');
        $emailVal = $model->getAttribute('email');
        $name = is_string($nameVal) ? $nameVal : '';
        $email = is_string($emailVal) ? $emailVal : '';

        return new DomainUser(
            id: DomainUserId::fromString($idStr),
            name: $name,
            email: $email,
            roles: $roles,
            permissions: $permissions,
            emailVerifiedAt: is_string($model->email_verified_at ?? null) ? (string) $model->email_verified_at : null,
            userType: is_string($model->user_type ?? null) ? (string) $model->user_type : null,
            avatar: is_string($model->avatar ?? null) ? (string) $model->avatar : null,
            createdAt: is_string($model->created_at ?? null) ? (string) $model->created_at : null,
            updatedAt: is_string($model->updated_at ?? null) ? (string) $model->updated_at : null,
        );
    }
}
