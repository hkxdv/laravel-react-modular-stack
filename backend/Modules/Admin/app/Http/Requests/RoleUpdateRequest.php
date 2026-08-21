<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validación de actualización de roles.
 */
final class RoleUpdateRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \Spatie\Permission\Models\Role $role */
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', sprintf('unique:roles,name,%d', $role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
