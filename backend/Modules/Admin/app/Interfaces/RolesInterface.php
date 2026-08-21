<?php

declare(strict_types=1);

namespace Modules\Admin\App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Interfaz para la gestión de roles del sistema.
 * Define las operaciones CRUD para roles en el módulo Admin.
 */
interface RolesInterface
{
    /**
     * Obtiene todos los roles del guard staff.
     *
     * @return Collection<int, Role> Colección de roles
     */
    public function getAllRoles(): Collection;

    /**
     * Obtiene todos los roles del guard staff con conteo de permisos eager-loaded.
     *
     * @return Collection<int, Role> Colección de roles con permissions_count
     */
    public function getAllRolesWithPermissionsCount(): Collection;

    /**
     * Obtiene el número total de roles del guard staff.
     *
     * @return int Total de roles
     */
    public function getTotalRoles(): int;

    /**
     * Crea un nuevo rol con los datos proporcionados.
     *
     * @param  array{name: string, permissions?: array<int, string>}  $data  Datos del rol
     * @return Role Rol creado
     */
    public function createRole(array $data): Role;

    /**
     * Actualiza un rol existente.
     *
     * @param  int  $id  ID del rol
     * @param  array{name?: string, permissions?: array<int, string>}  $data  Datos actualizados
     * @return Role Rol actualizado
     */
    public function updateRole(int $id, array $data): Role;

    /**
     * Elimina un rol por su ID. Los roles ADMIN y DEV no pueden eliminarse.
     *
     * @param  int  $id  ID del rol
     * @return bool Éxito de la operación
     */
    public function deleteRole(int $id): bool;

    /**
     * Asigna permisos a un rol y invalida la caché de permisos.
     *
     * @param  int  $roleId  ID del rol
     * @param  array<int, string>  $permissionNames  Nombres de los permisos a asignar
     */
    public function assignPermissionsToRole(int $roleId, array $permissionNames): void;

    /**
     * Obtiene los permisos asignados a un rol.
     *
     * @param  int  $roleId  ID del rol
     * @return Collection<int, \Spatie\Permission\Models\Permission> Permisos del rol
     */
    public function getRolePermissions(int $roleId): Collection;
}
