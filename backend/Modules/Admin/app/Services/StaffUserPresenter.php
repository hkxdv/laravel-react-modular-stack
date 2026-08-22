<?php

declare(strict_types=1);

namespace Modules\Admin\App\Services;

use App\Interfaces\AuthenticatableUser;
use Modules\Admin\App\Http\Resources\StaffUserResource;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Contracts\Auth\AuthUserPresenterInterface;

/**
 * Presenta usuarios StaffUser como arrays para props de Inertia.
 *
 * Delega a StaffUserResource cuando el usuario es un StaffUser;
 * retorna array vacío para otros tipos de AuthenticatableUser.
 */
final class StaffUserPresenter implements AuthUserPresenterInterface
{
    public function present(AuthenticatableUser $user): array
    {
        if ($user instanceof StaffUser) {
            $user->loadMissing('roles');

            return new StaffUserResource($user)->toArray(request());
        }

        return [];
    }
}
