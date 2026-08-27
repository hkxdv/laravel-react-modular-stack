<?php

declare(strict_types=1);

namespace Modules\Admin\App\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Admin\App\Domain\Filters\StaffUserFilter;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Domain\User\DomainUser;
use Spatie\Permission\Models\Role;

/**
 * Interfaz para la gestión de usuarios del personal administrativo.
 * Define las operaciones esenciales para administrar usuarios staff en el sistema.
 */
interface StaffUserManagerInterface
{
    /**
     * Obtiene una lista paginada de todos los usuarios con sus roles.
     *
     * @param  StaffUserFilter  $filter  Filtros y parámetros de ordenación
     * @return LengthAwarePaginator<int, StaffUser> Lista paginada de usuarios staff
     */
    public function getAllUsers(StaffUserFilter $filter): LengthAwarePaginator;

    /**
     * Crea un nuevo usuario con los datos proporcionados.
     *
     * @param  array<string, mixed>  $data  Datos del nuevo usuario (name, email, password, etc.)
     * @return DomainUser Usuario de dominio creado
     */
    public function createUser(array $data): DomainUser;

    /**
     * Obtiene un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return DomainUser|null Usuario de dominio encontrado o null
     */
    public function getUserById(int $id): ?DomainUser;

    /**
     * Actualiza un usuario existente.
     *
     * @param  int  $id  ID del usuario
     * @param  array<string, mixed>  $data  Datos actualizados (name, email, etc.)
     * @return DomainUser|null Usuario de dominio actualizado o null
     */
    public function updateUser(int $id, array $data): ?DomainUser;

    /**
     * Elimina un usuario por su ID.
     *
     * @param  int  $id  ID del usuario
     * @return bool Éxito de la operación
     */
    public function deleteUser(int $id): bool;

    /**
     * Sincroniza los roles de un usuario, preservando los roles protegidos.
     * Los roles protegidos (ADMIN y DEV) no pueden ser eliminados si ya están asignados.
     *
     * @param  StaffUser  $user  Usuario a actualizar
     * @param  array<string|int|Role>  $roles  Roles a asignar, pueden ser nombres de rol, IDs o instancias de Role
     */
    public function syncRoles(StaffUser $user, array $roles): void;

    /**
     * Obtiene el número total de usuarios registrados.
     *
     * @return int Total de usuarios
     */
    public function getTotalUsers(): int;
}
