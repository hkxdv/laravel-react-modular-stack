<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Modules\Admin\App\Models\StaffUser;

/**
 * Request para validación de datos de formulario de creación de usuarios del staff.
 */
final class CreateStaffUserRequest extends FormRequest
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

        return $user->hasPermissionTo('staff-users.create', 'staff');
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:128'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:42', 'unique:staff_users,email'],
            'password' => [
                'required',
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
            'password.required' => 'La contraseña es obligatoria',
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
}
