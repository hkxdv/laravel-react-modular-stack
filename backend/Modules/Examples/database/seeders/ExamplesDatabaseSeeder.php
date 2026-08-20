<?php

declare(strict_types=1);

namespace Modules\Examples\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Examples\App\Models\ExampleTenantUser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder esquelético para el módulo Examples (Tenant).
 *
 * Crea un usuario tenant de prueba con rol y permiso básico para guard 'tenant'.
 */
final class ExamplesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder del módulo Examples (Tenant)...');

        // Crear permiso para guard tenant
        Permission::query()->firstOrCreate([
            'name' => 'access-examples',
            'guard_name' => 'tenant',
        ]);

        // Crear rol ADMIN para guard tenant
        $role = Role::query()->firstOrCreate([
            'name' => 'ADMIN',
            'guard_name' => 'tenant',
        ]);
        $role->givePermissionTo('access-examples');

        // Crear usuario tenant de prueba
        $tenantEmail = 'tenant@domain.com';
        /** @var ExampleTenantUser|null $tenant */
        $tenant = ExampleTenantUser::query()->where('email', $tenantEmail)->first();

        if (! $tenant) {
            $tenant = ExampleTenantUser::query()->create([
                'name' => 'Tenant Example User',
                'email' => $tenantEmail,
                'password' => Hash::make('TenantPass123!'),
                'email_verified_at' => now(),
            ]);

            $tenant->assignRole($role);
            $this->command->info('Usuario tenant creado: '.$tenantEmail);
        } else {
            $this->command->info('Usuario tenant ya existe: '.$tenant->name);
        }

        $this->command->info('Seeder de Examples completado.');
    }
}
