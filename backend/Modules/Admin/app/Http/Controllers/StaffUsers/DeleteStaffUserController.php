<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Models\StaffUser;

/**
 * Controlador para la eliminación de un usuarios del personal administrativo.
 */
final class DeleteStaffUserController extends AbstractAdminController
{
    /**
     * Elimina un usuario existente.
     *
     * @param  int  $id  ID del usuario a eliminar
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            $user = $this->staffUserManager->getUserById($id);

            if (! ($user instanceof StaffUser)) {
                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'Usuario no encontrado. No se pudo realizar la eliminación.'
                    );
            }

            $hasProtectedRole = $user->roles
                ->pluck('name')
                ->contains(static fn ($name): bool => is_string($name)
                  && in_array(mb_strtoupper($name), ['ADMIN', 'DEV'], true));

            if ($hasProtectedRole) {
                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'No se puede eliminar un usuario con roles protegidos (ADMIN o DEV).'
                    );
            }

            $deleted = $this->staffUserManager->deleteUser($id);

            if ($deleted) {
                $nameAttr = $user->getAttribute('name');
                $userName = is_string($nameAttr) ? $nameAttr : '';

                return to_route('internal.staff.admin.users.index')
                    ->with(
                        'success',
                        sprintf("Usuario '%s' eliminado exitosamente.", $userName)
                    );
            }

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'error',
                    'No se pudo eliminar el usuario. Intente nuevamente.'
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al eliminar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $id,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'error',
                    'Ocurrió un error al eliminar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
