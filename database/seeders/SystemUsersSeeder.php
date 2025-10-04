<?php

namespace Database\Seeders;

use App\Models\StaffUsers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Dotenv\Dotenv;

/**
 * Seeder para crear múltiples usuarios base del sistema (guard: staff)
 * a partir de variables definidas en un archivo .env adicional (.env.users).
 *
 * Convención de variables:
 *   USER_STAFF_{N}_NAME=Nombre Apellido
 *   USER_STAFF_{N}_EMAIL=correo@dominio.com
 *   USER_STAFF_{N}_PASSWORD=contraseña_segura
 *   USER_STAFF_{N}_ROLE=ROL_OPCIONAL (ej. ADMIN, DEV, MOD-01, ...)
 *
 * Donde N es un número entre 1 y 50 (por defecto iteramos 1..10, pero se puede ampliar).
 */
class SystemUsersSeeder extends Seeder
{
    /**
     * Ejecuta el seeder para poblar la base de datos con usuarios base.
     */
    public function run(): void
    {
        $this->command->info('Iniciando seeder de Usuarios del Sistema (.env.users)...');

        // Intentar cargar .env.users desde rutas comunes sin fallar si no existe
        $this->loadUsersEnvIfExists();

        $created = 0;
        $updated = 0;
        $assignedRoles = 0;

        // Por defecto soportamos 10, pero si se define USER_STAFF_MAX se usa ese tope
        $max = (int) (env('USER_STAFF_MAX', 10));
        $max = $max > 0 ? min($max, 50) : 10; // límite de seguridad a 50

        for ($i = 1; $i <= $max; $i++) {
            $email = env("USER_STAFF_{$i}_EMAIL");
            $password = env("USER_STAFF_{$i}_PASSWORD");
            $name = env("USER_STAFF_{$i}_NAME", "Usuario {$i}");
            $roleName = env("USER_STAFF_{$i}_ROLE");

            if (!$email || !$password) {
                continue; // Se salta si faltan datos esenciales
            }

            $user = StaffUsers::where('email', $email)->first();
            if (!$user) {
                $user = StaffUsers::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);
                $created++;
            } else {
                $needsUpdate = false;
                $updateData = [];

                if ($user->name !== $name) {
                    $updateData['name'] = $name;
                    $needsUpdate = true;
                }

                // Si se cambia la contraseña en el .env.users, permite actualizarla explícitamente
                $forcePassword = env("USER_STAFF_{$i}_FORCE_PASSWORD_UPDATE", false);
                if ($forcePassword) {
                    $updateData['password'] = Hash::make($password);
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $user->update($updateData);
                    $updated++;
                }
            }

            // Asignar rol si se especifica y existe
            if ($roleName) {
                try {
                    $role = Role::findByName($roleName, 'staff');
                    if ($role && !$user->hasRole($role)) {
                        $user->assignRole($role);
                        $assignedRoles++;
                    }
                } catch (\Throwable $e) {
                    $this->command->warn("No se pudo asignar el rol '{$roleName}' al usuario {$email}: " . $e->getMessage());
                }
            }
        }

        Log::info('SystemUsersSeeder ejecutado', [
            'created' => $created,
            'updated' => $updated,
            'assigned_roles' => $assignedRoles,
            'max_checked' => $max,
        ]);

        $this->command->info("Usuarios creados: {$created}, actualizados: {$updated}, roles asignados: {$assignedRoles}.");
        $this->command->info('Seeder de usuarios del sistema completado.');
    }

    /**
     * Carga el archivo .env.users desde la raíz del repositorio si existe.
     */
    protected function loadUsersEnvIfExists(): void
    {
        // Solo leer .env.users en la raíz del repositorio (un nivel arriba de base_path())
        $rootPath = dirname(base_path());
        $file = $rootPath . DIRECTORY_SEPARATOR . '.env.users';

        if (is_file($file) && is_readable($file)) {
            try {
                // Usamos createMutable para permitir añadir variables extra al repositorio actual
                Dotenv::createMutable($rootPath, '.env.users')->safeLoad();
                $this->command->info("Cargado archivo de configuración de usuarios: {$file}");
                return;
            } catch (\Throwable $e) {
                $this->command->warn('No se pudo cargar .env.users: ' . $e->getMessage());
            }
        }

        $this->command->warn('No se encontró .env.users en la raíz del repositorio. Se usarán únicamente variables del entorno actual.');
    }
}
