<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforma el modelo StaffUser en un array para respuestas API.
 * Este recurso expone de forma segura los datos del usuario del personal,
 * incluyendo sus roles y permisos de forma condicional para optimizar el rendimiento.
 */
final class StaffUserResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'user_type' => 'staff',

            // Carga condicional de la relación 'roles' usando RoleResource para consistencia.
            'roles' => RoleResource::collection($this->whenLoaded('roles')),

            // Carga condicional de todos los permisos (directos y vía roles).
            // Se incluye solo si la relación 'roles' ha sido cargada,
            // ya que getAllPermissions() depende de ella para ser eficiente.
            'permissions' => $this->when($this->relationLoaded('roles'), function () {
                return $this->getAllPermissions()->pluck('name');
            }),
        ];
    }
}
