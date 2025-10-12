<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait PermissionVerifier
 * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
 *
 * Este trait es un wrapper sobre CrossGuardPermissions para usar en componentes
 * que no tienen acceso directo al usuario autenticado.
 */
trait PermissionVerifier
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico o alguno de una lista.
     *
     * @param  string|array<string>  $permissionName
     */
    public function can(string|array $permissionName): bool
    {
        /** @var \App\Models\StaffUsers|\App\Interfaces\AuthenticatableUser|null $user */
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Prioriza la verificación cross-guard si el método existe.
        // Este método ya incluye la lógica de super-admin (ADMIN/DEV) y el cacheo.
        if (method_exists($user, 'hasPermissionToCross')) {
            if (is_array($permissionName)) {
                // Usar implementación nativa si existe, de lo contrario iterar por cada permiso.
                if (method_exists($user, 'hasAnyPermissionCross')) {
                    return $user->hasAnyPermissionCross($permissionName);
                }

                foreach ($permissionName as $perm) {
                    if ($user->hasPermissionToCross($perm)) {
                        return true;
                    }
                }

                return false;
            }

            return $user->hasPermissionToCross($permissionName);
        }

        // Fallback a la verificación de permisos nativa de Laravel/Spatie si el trait no está.
        if (is_array($permissionName)) {
            foreach ($permissionName as $perm) {
                if ($user->can($perm)) {
                    return true;
                }
            }

            return false;
        }

        return $user->can($permissionName);
    }
}
