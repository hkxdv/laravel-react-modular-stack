<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Seeder para crear el usuario administrador inicial del sistema.
 */
final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Usuario Administrador...');

        $adminEmail = 'admin@domain.com';
        /** @var StaffUser|null $admin */
        $admin = StaffUser::query()->where('email', $adminEmail)->first();

        if (! $admin) {
            /** @var StaffUser $admin */
            $admin = StaffUser::query()->create([
                'name' => 'System Administrator',
                'email' => $adminEmail,
                'password' => Hash::make('AdminPass123!'),
                'email_verified_at' => now(),
            ]);

            $admin->forceFill([
                'password_changed_at' => now(),
                'last_activity' => now(),
            ])->save();

            // Asignar rol ADMIN al usuario creado
            try {
                $role = Role::findByName('ADMIN', 'staff');
                $admin->assignRole($role);
                $this->command->info(
                    'Usuario ADMIN creado y rol asignado: '.$adminEmail
                );
            } catch (Throwable $e) {
                $this->command->error(
                    'Error al asignar rol ADMIN: '.$e->getMessage()
                );
            }
        } else {
            $this->command->info('Usuario ADMIN ya existe: '.$admin->name);

            // Asegurar email_verified_at aunque el admin exista de una corrida parcial previa
            if ($admin->email_verified_at === null) {
                $admin->forceFill(['email_verified_at' => now()])->save();
                $this->command->info('email_verified_at establecido en admin existente.');
            }

            // Re-asignar rol ADMIN si se perdió (ej. truncate de model_has_roles)
            if (! $admin->hasRole('ADMIN', 'staff')) {
                try {
                    $role = Role::findByName('ADMIN', 'staff');
                    $admin->assignRole($role);
                    $this->command->info('Rol ADMIN re-asignado a admin existente.');
                } catch (Throwable $e) {
                    $this->command->error(
                        'Error al re-asignar rol ADMIN: '.$e->getMessage()
                    );
                }
            }
        }

        $this->command->info('Seeder completado.');
    }
}
