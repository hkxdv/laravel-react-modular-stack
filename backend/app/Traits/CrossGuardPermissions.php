<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Trait para extender la funcionalidad de Spatie Permission y permitir
 * verificaciones de permisos entre diferentes guards.
 *
 * Este trait debe ser la única fuente de verdad para verificar permisos entre guards.
 */
trait CrossGuardPermissions
{
    /**
     * Sincroniza los permisos y roles entre los guards 'web' y 'sanctum'.
     */
    public static function syncPermissionsBetweenGuards(): void
    {
        $guardsToSync = ['web', 'sanctum'];

        // Sincronizar Permisos
        $allPermissions = Permission::whereIn(
            'guard_name',
            $guardsToSync
        )->get()->groupBy('name');

        foreach ($allPermissions as $name => $permissions) {
            $existingGuards = $permissions->pluck('guard_name')->toArray();
            $missingGuards = array_diff($guardsToSync, $existingGuards);

            foreach ($missingGuards as $guard) {
                Permission::create(['name' => $name, 'guard_name' => $guard]);
            }
        }

        // Sincronizar Roles
        $allRoles = Role::whereIn(
            'guard_name',
            $guardsToSync
        )->with('permissions')->get()->groupBy('name');

        foreach ($allRoles as $name => $roles) {
            $existingGuards = $roles->pluck('guard_name')->toArray();
            $missingGuards = array_diff($guardsToSync, $existingGuards);

            $templateRole = $roles->firstWhere('guard_name', 'web')
                ?? $roles->first();

            foreach ($missingGuards as $guard) {
                $newRole = Role::firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
                $permissionsToSync = Permission::where('guard_name', $guard)
                    ->whereIn('name', $templateRole->permissions->pluck('name'))
                    ->get();
                $newRole->syncPermissions($permissionsToSync);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Verifica si el usuario tiene un permiso específico en cualquier guard, usando caché.
     */
    public function hasPermissionToCross(string $permission): bool
    {
        $permissionName = $permission;
        $cacheKey = 'user.'.$this->id.'.permission.'.$permissionName;

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($permission) {
            // Concede acceso inmediato a roles de alto nivel.
            if ($this->hasRoleCross(['ADMIN', 'DEV'])) {
                return true;
            }

            // Itera por los guards disponibles y valida el permiso.
            foreach ($this->getAvailableGuards() as $guard) {
                try {
                    if ($this->hasPermissionTo($permission, $guard)) {
                        return true;
                    }
                } catch (PermissionDoesNotExist $e) {
                    // El permiso no existe para este guard, continuar.
                    continue;
                }
            }

            return false;
        });
    }

    /**
     * Verifica si el usuario tiene alguno de los permisos especificados en cualquier guard.
     *
     * @param  string|array<string>|\Illuminate\Support\Collection<string>  $permissions
     */
    public function hasAnyPermissionCross($permissions): bool
    {
        $permissions = is_string($permissions) ? [$permissions] : $permissions;

        foreach ($permissions as $permission) {
            if ($this->hasPermissionToCross($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario tiene un rol específico en cualquier guard, usando caché.
     *
     * @param  string|array<string>|\Spatie\Permission\Contracts\Role|\Illuminate\Support\Collection  $roles
     */
    public function hasRoleCross($roles): bool
    {
        $roles = is_array($roles) || $roles instanceof \Illuminate\Support\Collection
            ? $roles
            : [$roles];
        $roleNames = collect($roles)->map(fn ($role) => is_object($role)
            ? $role->name
            : $role)->sort()->implode('.');
        $cacheKey = 'user.'.$this->id.'.roles.'.$roleNames;

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () use ($roles) {
                foreach ($this->getAvailableGuards() as $guard) {
                    try {
                        if ($this->hasRole($roles, $guard)) {
                            return true;
                        }
                    } catch (Exception $e) {
                        // Continuar si el rol no existe en el guard.
                        continue;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Obtiene todos los permisos del usuario en todos los guards, usando caché.
     *
     * @return array<string>
     */
    public function getAllCrossGuardPermissions(): array
    {
        $cacheKey = 'user.'.$this->id.'.all_cross_guard_permissions';

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            function () {
                if ($this->hasRoleCross(['ADMIN', 'DEV'])) {
                    return Permission::all()->pluck('name')
                        ->unique()->values()->toArray();
                }

                return $this->getAllPermissions()->pluck('name')
                    ->unique()->values()->toArray();
            }
        );
    }

    /**
     * Guards disponibles en la aplicación.
     *
     * @return array<string>
     */
    protected function getAvailableGuards(): array
    {
        return ['staff', 'web', 'sanctum'];
    }
}
