<?php

declare(strict_types=1);

namespace Modules\Admin\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Admin\App\Http\Resources\StaffUserResource;
use Modules\Admin\App\Models\StaffUser;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ListStaffUsersController Test
|--------------------------------------------------------------------------
|
| Verifies the ListStaffUsersController::index() response structure.
| Ensures the nested pagination shape required by the frontend:
| - users.data as array
| - users.links with first, last, prev, next keys
| - users.meta with current_page, total, per_page keys
|
*/

/**
 * @param  LengthAwarePaginator<int, StaffUser>  $paginator
 * @return array{data: array<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>}
 */
function decodeResourceCollectionResponse(LengthAwarePaginator $paginator): array
{
    $resourceCollection = StaffUserResource::collection($paginator);
    $response = $resourceCollection->toResponse(request());
    $content = $response->getContent();
    assert(is_string($content));
    /** @var array{data: array<array<string, mixed>>, links: array<string, mixed>, meta: array<string, mixed>} $result */
    $result = json_decode($content, true);

    return $result;
}

it('StaffUserResource::collection produces nested pagination shape via toResponse', function (): void {
    // Crear usuarios con rol staff
    $staffRole = Role::query()->create(['name' => 'staff-viewer', 'guard_name' => 'staff']);
    /** @var Collection<int, StaffUser> $users */
    $users = StaffUsersFactory::new()->count(3)->create();
    foreach ($users as $user) {
        /** @var StaffUser $user */
        $user->assignRole($staffRole);
    }

    // Simular paginator
    $paginator = new LengthAwarePaginator($users, 3, 15);

    $result = decodeResourceCollectionResponse($paginator);

    // Verificar estructura anidada: {data, links, meta}
    expect($result)->toHaveKeys(['data', 'links', 'meta']);

    // data debe ser un array (ya verificado por las claves)
    expect($result['data'])->toHaveCount(3);

    // links debe tener las claves de navegación
    expect($result['links'])->toHaveKeys(['first', 'last', 'prev', 'next']);

    // meta debe tener las claves de paginación requeridas
    expect($result['meta'])->toHaveKeys(['current_page', 'total', 'per_page']);
    expect($result['meta']['total'])->toBe(3);
});

it('StaffUserResource transforms StaffUser to array correctly', function (): void {
    $staffRole = Role::query()->create(['name' => 'staff-viewer', 'guard_name' => 'staff']);
    /** @var StaffUser $user */
    $user = StaffUsersFactory::new()->create();
    $user->assignRole($staffRole);

    $resource = new StaffUserResource($user);
    $result = $resource->toArray(request());

    expect($result)->toHaveKeys(['id', 'name', 'email', 'user_type']);
    expect($result['user_type'])->toBe('staff');
});
