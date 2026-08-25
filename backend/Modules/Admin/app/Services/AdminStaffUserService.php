<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Admin\App\Domain\Filters\StaffUserFilter;
use Modules\Admin\App\Interfaces\RolesInterface;
use Modules\Admin\App\Interfaces\StaffUserManagerInterface;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Contracts\PermissionVerifierInterface;
use Modules\Core\Domain\User\DomainUser;
use Modules\Core\Infrastructure\Laravel\Mappers\DomainUserMapper;
use Spatie\Permission\Models\Role;

/**
 * Servicio para manejar la lógica de negocio de la gestión de usuarios del personal (Staff).
 * Implementa las operaciones definidas en la interfaz StaffUserManagerInterface.
 */
final readonly class AdminStaffUserService implements StaffUserManagerInterface
{
    /**
     * Parámetros de ordenación permitidos para esta servicio.
     *
     * @var list<string>
     */
    private const array ALLOWED_SORT_FIELDS = [
        'id',
        'name',
        'email',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private PermissionVerifierInterface $permissionVerifier,
        private RolesInterface $rolesInterface,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     *
     * @return LengthAwarePaginator<int, DomainUser>
     */
    public function getAllUsers(StaffUserFilter $filter): LengthAwarePaginator
    {
        $query = StaffUser::query()
            // Eliminamos 'avatar' del select porque es un atributo computado
            ->select('id', 'name', 'email', 'created_at', 'updated_at')
            ->with(['roles']);

        // Filtrado por término de búsqueda
        if ($filter->search !== null && $filter->search !== '') {
            $searchTerm = $filter->search;
            $query->where(
                function ($q) use ($searchTerm): void {
                    $q->where('name', 'like', sprintf('%%%s%%', $searchTerm))
                        ->orWhere('email', 'like', sprintf('%%%s%%', $searchTerm));
                }
            );
        }

        // Filtrado por rol específico
        if ($filter->role !== null && $filter->role !== '') {
            $query->whereHas(
                'roles',
                function ($q) use ($filter): void {
                    $q->where('name', $filter->role);
                }
            );
        }

        // Ordenamiento
        $sortField = $filter->sortField;
        $sortDirection = $filter->sortDirection;

        if (in_array($sortField, self::ALLOWED_SORT_FIELDS, true)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginar los resultados
        $paginator = $query->paginate($filter->perPage);

        /** @var LengthAwarePaginator<int, DomainUser> $result */
        $result = $this->mapPaginatedDomainUser($paginator);

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * Efectos secundarios:
     * - Establece `email_verified_at` si `auto_verify_email` es verdadero (por defecto).
     * - Inicializa `password_changed_at` al momento de creación.
     * - Sincroniza roles si se proporcionan en `data['roles']`.
     */
    public function createUser(array $data): DomainUser
    {
        // Determinar si se debe verificar automáticamente el email (por defecto: true)
        $shouldAutoVerify = ! isset($data['auto_verify_email'])
            || $data['auto_verify_email'] === true;

        if ($shouldAutoVerify) {
            $data['email_verified_at'] = now();
        }

        // Eliminar el campo auto_verify_email antes de crear el usuario
        if (isset($data['auto_verify_email'])) {
            unset($data['auto_verify_email']);
        }

        // Crear el usuario con los datos proporcionados
        $user = StaffUser::query()->create($data);
        // Inicializar fecha de establecimiento de contraseña
        $user->forceFill([
            'password_changed_at' => now(),
        ])->save();

        // Si no se verificará automáticamente, enviar notificación de verificación
        if (! $shouldAutoVerify) {
            $user->sendEmailVerificationNotification();
        }

        // Asignar roles si se proporcionan
        if (! empty($data['roles']) && is_array($data['roles'])) {
            /** @var array<int, string|int|Role> $roles */
            $roles = $data['roles'];
            $this->syncRoles($user, $roles);
        }

        return $this->mapToDomain($user);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserById(int $id): ?DomainUser
    {
        $user = StaffUser::with('roles', 'permissions')->find($id);

        return $user ? $this->mapToDomain($user) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function updateUser(int $id, array $data): ?DomainUser
    {
        $user = StaffUser::query()->find($id);
        if ($user) {
            // Extraer password_changed_at si viene en payload y evitar mass assignment
            $pwdChangedAt = $data['password_changed_at'] ?? null;
            if (array_key_exists('password_changed_at', $data)) {
                unset($data['password_changed_at']);
            }

            $user->update($data);

            if ($pwdChangedAt !== null) {
                $user->forceFill([
                    'password_changed_at' => $pwdChangedAt,
                ])->save();
            }

            return $this->mapToDomain($user);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteUser(int $id): bool
    {
        $user = StaffUser::query()->find($id);
        if ($user) {
            return (bool) $user->delete();
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function syncRoles(StaffUser $user, array $roles): void
    {
        // 1. Filtrar los roles 'ADMIN' y 'DEV' de la solicitud.
        // Normaliza roles a nombres o IDs y filtra ADMIN/DEV
        /** @var array<int, string|int> $assignableRoles */
        $assignableRoles = array_values(array_filter(array_map(
            static function (string|int|Role $role): string|int {
                // Enteros y cadenas pasan directamente
                if (is_int($role) || is_string($role)) {
                    return $role;
                }

                // En este punto, por el filtro previo, $role es Role
                return $role->name; // usar nombre para evitar colisiones
            },
            $roles
        ), static fn (string|int $roleName): bool => (string) $roleName !== '' &&
            ! in_array(
                mb_strtoupper((string) $roleName),
                ['ADMIN', 'DEV'],
                true
            )));

        // 2. Obtener los nombres de roles protegidos que el usuario ya tiene.
        /** @var array<int, string> $protectedRoles */
        $protectedRoles = array_values(array_filter(
            $user->roles
                ->pluck('name')
                ->all(),
            static fn ($name): bool => is_string($name) && in_array(
                mb_strtoupper($name),
                ['ADMIN', 'DEV'],
                true
            )
        ));

        // 3. Unir los roles asignables con los protegidos existentes.
        /** @var array<int, string|int> $finalRoles */
        $finalRoles = array_values(array_unique(
            array_merge($assignableRoles, $protectedRoles)
        ));

        $user->syncRoles($finalRoles);

        $this->permissionVerifier->clearCache($user);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalUsers(): int
    {
        return StaffUser::query()->count();
    }

    /**
     * Obtiene el número total de roles definidos para el guard staff.
     *
     * @return int Total de roles
     */
    public function getTotalRoles(): int
    {
        return Role::query()->where('guard_name', 'staff')->count();
    }

    /**
     * Obtiene todos los roles disponibles para el guard staff.
     *
     * @return Collection<int, Role> Colección de roles
     */
    public function getAllRoles(): Collection
    {
        return $this->rolesInterface->getAllRoles();
    }

    /**
     * Convierte un modelo Eloquent StaffUser a entidad de dominio DomainUser.
     */
    private function mapToDomain(StaffUser $user): DomainUser
    {
        return DomainUserMapper::toDomain($user);
    }

    /**
     * Convierte un paginador de StaffUser a paginador de DomainUser.
     *
     * @param  LengthAwarePaginator<int, StaffUser>  $paginator
     * @return LengthAwarePaginator<int, DomainUser>
     */
    private function mapPaginatedDomainUser(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $items = $paginator->all();
        $mappedItems = array_map(
            $this->mapToDomain(...),
            $items
        );

        /** @var LengthAwarePaginator<int, DomainUser> $result */
        $result = $this->newPaginatorWithCollection($paginator, $mappedItems);

        return $result;
    }

    /**
     * Crea un nuevo paginador con la colección mapeada.
     *
     * @param  LengthAwarePaginator<int, StaffUser>  $paginator
     * @param  array<int, DomainUser>  $items
     * @return LengthAwarePaginator<int, DomainUser>
     */
    private function newPaginatorWithCollection(LengthAwarePaginator $paginator, array $items): LengthAwarePaginator
    {
        /** @var \Illuminate\Support\Collection<int, DomainUser> $mappedCollection */
        $mappedCollection = collect($items);

        // setCollection() expects Collection<StaffUser> (TValue from the paginator).
        // We pass Collection<DomainUser> — semantically correct (DomainUser replaces StaffUser items).
        // Call-site variance tells PHPStan to accept the narrower type at this call.
        /** @var LengthAwarePaginator<int, DomainUser> $result */
        $result = $this->setCollectionWithDomainUser($paginator, $mappedCollection);

        return $result;
    }

    /**
     * Wrapper para setCollection que informa a PHPStan del tipo DomainUser.
     *
     * @param  LengthAwarePaginator<int, StaffUser>  $paginator
     * @param  \Illuminate\Support\Collection<int, DomainUser>  $collection
     * @return LengthAwarePaginator<int, DomainUser>
     */
    private function setCollectionWithDomainUser(
        LengthAwarePaginator $paginator,
        \Illuminate\Support\Collection $collection,
    ): LengthAwarePaginator {
        /** @var LengthAwarePaginator<int, DomainUser> $result */
        // @phpstan-ignore argument.type (DomainUser replaces StaffUser in the collection)
        $result = $paginator->setCollection($collection);

        return $result;
    }
}
