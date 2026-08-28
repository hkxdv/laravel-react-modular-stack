<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Spatie\Permission\Models\Role;

/**
 * Servicio para manejar la lógica de negocio de la gestión de roles.
 * Implementa las operaciones definidas en la interfaz RolesInterface.
 */
final readonly class RoleService implements RolesInterface
{
    /**
     * Roles protegidos que no pueden eliminarse ni renombrarse.
     *
     * @var array<int, string>
     */
    private const array PROTECTED_ROLES = ['ADMIN', 'DEV'];

    public function __construct(
        private PermissionVerifierInterface $permissionVerifier,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getAllRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'staff')
            ->get(['id', 'name', 'guard_name']);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllRolesWithPermissionsCount(): Collection
    {
        return Role::query()
            ->where('guard_name', 'staff')
            ->withCount('permissions')
            ->get(['id', 'name', 'guard_name']);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalRoles(): int
    {
        return Role::query()->where('guard_name', 'staff')->count();
    }

    /**
     * {@inheritDoc}
     */
    public function createRole(array $data): Role
    {
        /** @var Role $role */
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'staff',
        ]);

        if (isset($data['permissions'])) {
            $this->assignPermissionsToRole((int) $role->id, $data['permissions']);
        }

        return $role;
    }

    /**
     * Actualiza un rol existente con los datos proporcionados.
     *
     * @param  int  $id  ID del rol
     * @param  array{name?: string, permissions?: array<int, string>}  $data  Datos actualizados (parcial)
     * @return Role Rol actualizado
     *
     * @throws InvalidArgumentException Si no se proporciona al menos un campo (name o permissions)
     */
    public function updateRole(int $id, array $data): Role
    {
        $role = Role::query()->findOrFail($id);

        throw_if(
            ! isset($data['name']) &&
            ! isset($data['permissions']),
            InvalidArgumentException::class,
            'Update payload must contain at least one of: name, permissions.'
        );

        if (isset($data['name']) && $role->name !== $data['name']) {
            if (in_array(mb_strtoupper($role->name), self::PROTECTED_ROLES, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => sprintf('No se puede renombrar el rol protegido "%s".', $role->name),
                ]);
            }

            $role->name = $data['name'];
            $role->save();
        }

        if (isset($data['permissions'])) {
            $this->assignPermissionsToRole((int) $role->id, $data['permissions']);
        }

        return $role;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteRole(int $id): bool
    {
        $role = Role::query()->findOrFail($id);

        if (in_array(mb_strtoupper($role->name), self::PROTECTED_ROLES, true)) {
            return false;
        }

        return (bool) $role->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function assignPermissionsToRole(int $roleId, array $permissionNames): void
    {
        $role = Role::query()->findOrFail($roleId);
        $role->syncPermissions($permissionNames);

        /** @var Collection<int, \Modules\Admin\App\Models\StaffUser> $affectedUsers */
        $affectedUsers = $role->users;
        foreach ($affectedUsers as $user) {
            $this->permissionVerifier->clearCache($user);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return Collection<int, \Spatie\Permission\Models\Permission>
     */
    public function getRolePermissions(int $roleId): Collection
    {
        $role = Role::query()->findOrFail($roleId);

        // Spatie's Eloquent\Collection<int, Model> is structurally Collection<int, Permission>
        // but PHPStan can't infer the morphic relation's generic. The @return annotation is authoritative.
        // @phpstan-ignore return.type
        return $role->permissions;
    }
}
