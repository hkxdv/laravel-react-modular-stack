<?php

declare(strict_types=1);

namespace Modules\Admin\App\Domain\Filters;

use Illuminate\Http\Request;

/**
 * DTO para filtros de listado de usuarios del personal.
 * Transporte inmmutable de parámetros de filtrado y ordenación.
 */
final readonly class StaffUserFilter
{
    /**
     * @param  string|null  $search  Término de búsqueda (name o email)
     * @param  string|null  $role  Filtrado por nombre de rol
     * @param  string  $sortField  Campo para ordenar resultados
     * @param  string  $sortDirection  Dirección de ordenamiento (asc/desc)
     * @param  int  $perPage  Número de elementos por página
     */
    public function __construct(
        public ?string $search = null,
        public ?string $role = null,
        public string $sortField = 'created_at',
        public string $sortDirection = 'desc',
        public int $perPage = 10,
    ) {
        //
    }

    /**
     * Construye el DTO a partir de una solicitud HTTP.
     */
    public static function fromRequest(Request $request): self
    {
        $searchInput = $request->input('search');
        $roleInput = $request->input('role');
        $sortFieldInput = $request->input('sort_field');
        $sortDirectionInput = $request->input('sort_direction');
        $perPageInput = $request->input('per_page');

        return new self(
            search: is_string($searchInput) ? $searchInput : null,
            role: is_string($roleInput) ? $roleInput : null,
            sortField: is_string($sortFieldInput) ? $sortFieldInput : 'created_at',
            sortDirection: is_string($sortDirectionInput) ? $sortDirectionInput : 'desc',
            perPage: is_numeric($perPageInput) ? (int) $perPageInput : 10,
        );
    }
}
