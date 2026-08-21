<?php

declare(strict_types=1);

namespace Modules\Admin\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validación de creación de roles.
 */
final class RoleCreateRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
