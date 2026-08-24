<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\App\Models\StaffUser;

/**
 * Request para validación de datos de formulario de actualización de usuarios del staff.
 */
final class UpdateStaffUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool Verdadero si el usuario puede acceder; falso en caso contrario.
     */
    public function authorize(): bool
    {
        /** @var StaffUser|null $user */
        $user = Auth::user();

        if (! $user instanceof StaffUser) {
            return false;
        }

        return $user->hasPermissionTo('staff-users.update', 'staff');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var StaffUser|null $staffUser */
        $staffUser = $this->route('staffUser');
        $userId = null;
        if ($staffUser !== null) {
            $key = $staffUser->getKey();
            $userId = is_numeric($key) ? (int) $key : null;
        }

        return [
            'name' => ['required', 'string', 'min:3', 'max:128'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:42',
                sprintf('unique:staff_users,email,%d', (int) $userId),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,name'],
            'avatar' => ['nullable', 'string', 'max:2048'],
            'auto_verify_email' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Obtiene mensajes personalizados para errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos :min caracteres',
            'name.max' => 'El nombre no debe exceder los :max caracteres',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ingresar un correo electrónico válido',
            'email.unique' => 'Este correo electrónico ya está en uso',
            'email.max' => 'El correo electrónico no debe exceder los :max caracteres',
            'password.min' => 'La contraseña debe tener al menos :min caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.mixed' => 'La contraseña debe contener al menos una letra mayúscula y una minúscula',
            'password.numbers' => 'La contraseña debe contener al menos un número',
            'password.symbols' => 'La contraseña debe contener al menos un símbolo',
            'password.uncompromised' => 'La contraseña proporcionada ha aparecido en una filtración de datos. Por favor, elija una contraseña diferente.',
            'roles.required' => 'Debe seleccionar al menos un rol',
            'roles.min' => 'Debe seleccionar al menos un rol',
            'roles.*.exists' => 'Uno de los roles seleccionados no es válido',
            'avatar.max' => 'La URL o datos de la imagen no deben exceder :max caracteres',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            // Solo aplicar en actualizaciones
            if (! $this->isMethod('PUT') && ! $this->isMethod('PATCH')) {
                return;
            }

            /** @var StaffUser|null $user */
            $user = $this->route('staffUser');
            if (! $user instanceof StaffUser) {
                return;
            }

            // Consideramos protegido a cualquier usuario que tenga roles ADMIN o DEV
            /** @var array<int, string> $roleNames */
            $roleNames = array_filter((array) $this->input(
                'roles',
                []
            ), is_string(...));
            $requestRoles = array_values(array_map(
                mb_strtoupper(...),
                $roleNames
            ));

            $currentProtectedRoles = $user->roles
                ->pluck('name')
                ->map(
                    static fn ($name): string => is_string($name)
                        ? mb_strtoupper($name) : ''
                )
                ->filter(
                    static fn (string $name): bool => $name !== ''
                        && in_array($name, ['ADMIN', 'DEV'], true)
                )
                ->values()
                ->all();

            // Verificar que todos los roles protegidos actuales sigan presentes en la solicitud
            $remainingProtectedRoles = array_intersect(
                $currentProtectedRoles,
                $requestRoles
            );

            if (
                count($remainingProtectedRoles)
                !== count($currentProtectedRoles)
            ) {
                $validator->errors()->add(
                    'roles',
                    'No se pueden remover roles protegidos de un usuario administrador'
                );
            }
        });
    }
}
