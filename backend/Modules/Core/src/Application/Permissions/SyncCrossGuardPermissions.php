<?php

declare(strict_types=1);

namespace Modules\Core\Application\Permissions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Caso de uso: sincronizar permisos/roles entre guards.
 */
final readonly class SyncCrossGuardPermissions
{
    /**
     * Sincroniza permisos/roles entre los guards configurados.
     *
     * Lee los guards desde config('core.guards') y excluye los de
     * config('core.sync_excludes').
     */
    public function handle(): void
    {
        /** @var array<string, mixed> $allGuardsConfig */
        $allGuardsConfig = config('core.guards', []);
        $allGuards = array_keys($allGuardsConfig);
        /** @var array<string> $syncExcludes */
        $syncExcludes = config('core.sync_excludes', ['staff']);
        $guardsToSync = array_values(array_diff($allGuards, $syncExcludes));

        // Sincronizar Permisos
        $allPermissions = Permission::query()->whereIn('guard_name', $guardsToSync)->get()->groupBy('name');

        foreach ($allPermissions as $name => $permissions) {
            /** @var array<string> $existingGuards */
            $existingGuards = $permissions->pluck('guard_name')->toArray();
            $missingGuards = array_diff($guardsToSync, $existingGuards);

            foreach ($missingGuards as $guard) {
                Permission::create(['name' => $name, 'guard_name' => $guard]);
            }
        }

        // Sincronizar Roles
        $allRoles = Role::query()->whereIn('guard_name', $guardsToSync)
            ->with('permissions')
            ->get()
            ->groupBy('name');

        foreach ($allRoles as $name => $roles) {
            /** @var array<string> $existingGuards */
            $existingGuards = $roles->pluck('guard_name')->toArray();
            $missingGuards = array_diff($guardsToSync, $existingGuards);

            $templateRole = $roles->firstWhere('guard_name', 'web')
                ?? $roles->first();
            if (! $templateRole) {
                continue;
            }

            foreach ($missingGuards as $guard) {
                $newRole = Role::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
                $permissionsToSync = Permission::query()->where('guard_name', $guard)
                    ->whereIn('name', $templateRole->permissions->pluck('name'))
                    ->get();
                $newRole->syncPermissions($permissionsToSync);
            }
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
