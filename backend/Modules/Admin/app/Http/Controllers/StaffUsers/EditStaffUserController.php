<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Controllers\StaffUsers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Facades\Log;
use Inertia\Response as InertiaResponse;
use Modules\Admin\App\Http\Controllers\AbstractAdminController;
use Modules\Admin\App\Http\Controllers\StaffUsers\Concerns\NormalizesStaffUserPayload;
use Modules\Admin\App\Http\Requests\UpdateStaffUserRequest;
use Modules\Admin\App\Models\StaffUser;

use function Foundry\Helpers\modelStringAttribute;

/**
 * Controlador para la edición de usuarios del personal administrativo.
 */
final class EditStaffUserController extends AbstractAdminController
{
    use NormalizesStaffUserPayload;

    /**
     * Muestra el formulario de edición de un usuario existente.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @param  StaffUser  $user  Usuario a editar (inyectado por la ruta)
     * @return InertiaResponse Respuesta Inertia con el formulario de edición
     */
    public function edit(IlluminateRequest $request, StaffUser $user): InertiaResponse
    {
        $roles = $this->staffUserManager->getAllRoles();

        $additionalData = [
            'user' => $user,
            'roles' => $roles,
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: 'user/edit'
        );
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param  UpdateStaffUserRequest  $request  Solicitud validada para actualización de usuario
     * @param  StaffUser  $user  Usuario a actualizar (inyectado por la ruta)
     * @return RedirectResponse Redirección con mensaje de éxito
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación de entrada falla.
     */
    public function update(UpdateStaffUserRequest $request, StaffUser $user): RedirectResponse
    {
        try {
            $validatedData = $this->buildUpdatePayload($request);

            $this->staffUserManager->updateUser($user->id, $validatedData);

            $filteredRoles = $this->normalizeRoleInputs($request);
            if ($filteredRoles !== []) {
                $this->staffUserManager->syncRoles($user, $filteredRoles);
            }

            $userName = modelStringAttribute($user, 'name', '');

            return to_route('internal.staff.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' actualizado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al actualizar usuario: '.$exception->getMessage(),
                [
                    'user_id' => $user->id,
                    'data' => $request->except([
                        'password',
                        'password_confirmation',
                    ]),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            return back()
                ->withInput($request->except([
                    'password',
                    'password_confirmation',
                ]))
                ->with(
                    'error',
                    'Ocurrió un error al actualizar el usuario. Por favor, inténtalo nuevamente.'
                );
        }
    }
}
