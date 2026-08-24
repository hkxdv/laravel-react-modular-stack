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
     * @param  StaffUser  $user  Usuario a eliminar (inyectado por la ruta)
     * @return RedirectResponse Redirección con mensaje de éxito o error
     */
    public function destroy(StaffUser $user): RedirectResponse
    {
        $response = null;

        try {
            $hasProtectedRole = $user->roles
                ->pluck('name')
                ->contains(static fn ($name): bool => is_string($name)
                  && in_array(mb_strtoupper($name), ['ADMIN', 'DEV'], true));

            if ($hasProtectedRole) {
                $response = to_route('internal.staff.admin.users.index')
                    ->with(
                        'error',
                        'No se puede eliminar un usuario con roles protegidos.'
                    );
            } else {
                $deleted = $this->staffUserManager->deleteUser($user->id);

                if ($deleted) {
                    $userName = $user->getAttribute('name');
                    $userName = is_string($userName) ? $userName : '';

                    $response = to_route('internal.staff.admin.users.index')
                        ->with(
                            'success',
                            sprintf("Usuario '%s' eliminado exitosamente.", $userName)
                        );
                } else {
                    $response = to_route('internal.staff.admin.users.index')
                        ->with(
                            'error',
                            'No se pudo eliminar el usuario. Intente nuevamente.'
                        );
                }
            }
        } catch (Exception $exception) {
            Log::error(
                'Error al eliminar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $user->id,
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            $response = to_route('internal.staff.admin.users.index')
                ->with(
                    'error',
                    'Ocurrió un error al eliminar el usuario. Por favor, inténtalo nuevamente.'
                );
        }

        return $response;
    }
}
