<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder para crear los roles, permisos y usuarios base del sistema.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la base de datos.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Roles y Permisos...');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // CREAR PERMISOS - Solo los esenciales para acceso a módulos
        $permissions = [
            'access-module-01',
            'access-module-02',
            // 'access-module-03',
            // 'access-module-04',
            'access-admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'staff'
            ]);
        }
        $this->command->info('Permisos creados.');

        // CREAR ROLES Y ASIGNAR PERMISOS
        // Rol: ADMIN (super-admin con todos los permisos)
        $roleAdmin = Role::firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'staff'
        ]);
        $roleAdmin->givePermissionTo($permissions);

        // Rol: DEV (también es super-admin con todos los permisos)
        $roleDev = Role::firstOrCreate([
            'name' => 'DEV',
            'guard_name' => 'staff'
        ]);
        $roleDev->givePermissionTo($permissions);

        // Roles de Módulos (MOD-XX) - cada uno solo con su permiso principal
        Role::firstOrCreate([
            'name' => 'MOD-01',
            'guard_name' => 'staff'
        ])->givePermissionTo('access-module-01');

        Role::firstOrCreate([
            'name' => 'MOD-02',
            'guard_name' => 'staff'
        ])->givePermissionTo('access-module-02');

        /* 
        Role::firstOrCreate([
            'name' => 'MOD-03',
            'guard_name' => 'staff'
        ])->givePermissionTo('access-module-03');

        Role::firstOrCreate([
            'name' => 'MOD-04',
            'guard_name' => 'staff'
        ])->givePermissionTo('access-module-04'); 
        */

        // NOTA: La creación de usuarios ahora es manejada por SystemUsersSeeder
        // para mejor separación de responsabilidades y flexibilidad.

        // Registrar información en el log
        Log::info('Seeder de roles y permisos ejecutado:', [
            'roles_count' => Role::count(),
            'permissions_count' => Permission::count(),
            'roles' => Role::all(['id', 'name', 'guard_name'])->toArray()
        ]);

        $this->command->info('Seeder de roles y permisos completado exitosamente.');
    }
}
