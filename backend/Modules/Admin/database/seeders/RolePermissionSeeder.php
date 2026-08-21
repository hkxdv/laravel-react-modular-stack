<?php

declare(strict_types=1);

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder para crear los roles y permisos granulares del sistema.
 */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Roles y Permisos granulares...');

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // PERMISOS STAFF (16 granulares)
        $staffPermissions = [
            // Core
            'system.bypass',
            'permissions.sync',
            // Admin / RBAC
            'rbac.view',
            'staff-users.view',
            'staff-users.create',
            'staff-users.update',
            'staff-users.delete',
            'staff-users.impersonate',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.manage',
            // Module02
            'module02.dashboard.access',
            // Examples (staff)
            'examples.dashboard.access',
        ];

        // PERMISOS TENANT (4 granulares)
        $tenantPermissions = [
            'examples.tenant.login',
            'examples.tenant.dashboard',
            'examples.tenant.manage',
            'examples.tenant.logout',
        ];

        // Crear permisos staff
        foreach ($staffPermissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'staff',
            ]);
        }

        // Crear permisos tenant
        foreach ($tenantPermissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'tenant',
            ]);
        }

        $this->command->info('Permisos granulares creados: '.count($staffPermissions).' staff + '.count($tenantPermissions).' tenant.');

        // ROLES STAFF
        // ADMIN: todos los permisos staff + system.bypass
        $roleAdmin = Role::query()->firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'staff',
        ]);
        $roleAdmin->givePermissionTo($staffPermissions);

        // DEV: todos los permisos staff + system.bypass
        $roleDev = Role::query()->firstOrCreate([
            'name' => 'DEV',
            'guard_name' => 'staff',
        ]);
        $roleDev->givePermissionTo($staffPermissions);

        // MOD-01: solo permisos de examples dashboard (staff)
        Role::query()->firstOrCreate([
            'name' => 'MOD-01',
            'guard_name' => 'staff',
        ])->givePermissionTo('examples.dashboard.access');

        // MOD-02: solo permisos de module02
        Role::query()->firstOrCreate([
            'name' => 'MOD-02',
            'guard_name' => 'staff',
        ])->givePermissionTo('module02.dashboard.access');

        // ROLES TENANT
        // ADMIN (tenant): todos los permisos tenant
        $roleTenantAdmin = Role::query()->firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'tenant',
        ]);
        $roleTenantAdmin->givePermissionTo($tenantPermissions);

        Log::info('Seeder de roles y permisos granulares ejecutado:', [
            'roles_count' => Role::query()->count(),
            'permissions_count' => Permission::query()->count(),
        ]);

        $this->command->info('Seeder de roles y permisos granulares completado.');
    }
}
