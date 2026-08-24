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
use Modules\Admin\App\Http\Requests\CreateStaffUserRequest;

use function Foundry\Helpers\modelStringAttribute;

/**
 * Controlador para la creación de usuarios del personal administrativo.
 */
final class CreateStaffUserController extends AbstractAdminController
{
    use NormalizesStaffUserPayload;

    private const CREATE_ERROR_MESSAGE = 'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente.';

    private const CREATE_VIEW = 'user/create';

    /**
     * Muestra el formulario de creación de un nuevo usuario.
     *
     * @param  IlluminateRequest  $request  Solicitud HTTP
     * @return InertiaResponse Respuesta Inertia con el formulario de creación
     */
    public function create(IlluminateRequest $request): InertiaResponse
    {
        $roles = $this->staffUserManager->getAllRoles();

        $additionalData = [
            'roles' => $roles,
        ];

        return $this->orchestrator->renderModuleView(
            request: $request,
            moduleSlug: $this->getModuleSlug(),
            additionalData: $additionalData,
            navigationService: $this->navigationBuilder,
            view: self::CREATE_VIEW
        );
    }

    /**
     * Almacena un nuevo usuario.
     *
     * @param  CreateStaffUserRequest  $request  Solicitud validada para creación de usuario
     * @return RedirectResponse|InertiaResponse Redirección o respuesta Inertia
     *
     * @throws \Illuminate\Validation\ValidationException Si la validación de entrada falla.
     */
    public function store(CreateStaffUserRequest $request): RedirectResponse|InertiaResponse
    {
        $isInertiaRequest = (bool) $request->header('X-Inertia');
        $response = null;

        if ($isInertiaRequest) {
            try {
                $validatedData = $this->buildCreatePayload($request);
                $user = $this->staffUserManager->createUser($validatedData);

                $userName = modelStringAttribute($user, 'name', '');
                session()->flash(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $userName)
                );

                $roles = $this->staffUserManager->getAllRoles();

                $additionalData = [
                    'roles' => $roles,
                    'user' => $user,
                    'preventRedirect' => true,
                ];

                $response = $this->orchestrator->renderModuleView(
                    request: $request,
                    moduleSlug: $this->getModuleSlug(),
                    additionalData: $additionalData,
                    navigationService: $this->navigationBuilder,
                    view: self::CREATE_VIEW
                );
            } catch (Exception $exception) {
                Log::error(
                    'Error al crear usuario: '.$exception->getMessage(),
                    [
                        'data' => $request->except(['password', 'password_confirmation']),
                        'trace' => $exception->getTraceAsString(),
                    ]
                );

                session()->flash(
                    'error',
                    self::CREATE_ERROR_MESSAGE
                );

                $roles = $this->staffUserManager->getAllRoles();

                $additionalData = [
                    'roles' => $roles,
                    'errors' => [
                        'general' => self::CREATE_ERROR_MESSAGE,
                    ],
                ];

                $response = $this->orchestrator->renderModuleView(
                    request: $request,
                    moduleSlug: $this->getModuleSlug(),
                    additionalData: $additionalData,
                    navigationService: $this->navigationBuilder,
                    view: self::CREATE_VIEW
                );
            }
        }

        try {
            $validatedData = $this->buildCreatePayload($request);
            $user = $this->staffUserManager->createUser($validatedData);

            $userName = modelStringAttribute($user, 'name', '');

            $response = to_route('internal.staff.admin.users.index')
                ->with(
                    'success',
                    sprintf("Usuario '%s' creado exitosamente.", $userName)
                );
        } catch (Exception $exception) {
            Log::error(
                'Error al crear usuario: '.$exception->getMessage(),
                [
                    'data' => $request->except(['password', 'password_confirmation']),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            $response = back()
                ->withInput(
                    $request->except(['password', 'password_confirmation'])
                )
                ->with(
                    'error',
                    self::CREATE_ERROR_MESSAGE
                );
        }

        return $response;
    }
}
