<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Valida los datos para la actualización del perfil de un usuario del personal.
 */
final class ProfileUpdateRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique($this->resolveUserModelClass())->ignore(
                    $this->user()?->getAuthIdentifier()
                ),
            ],
        ];
    }

    /**
     * Obtiene los mensajes de error personalizados para las reglas de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está en uso por otro usuario.',
        ];
    }

    /**
     * Resuelve la clase del modelo de usuario desde la config de auth.
     */
    private function resolveUserModelClass(): string
    {
        $guardName = Auth::getDefaultDriver();
        /** @var string $provider */
        $provider = config(sprintf('auth.guards.%s.provider', $guardName), '');
        /** @var string $model */
        $model = config(sprintf('auth.providers.%s.model', $provider), '');

        return $model;
    }
}
