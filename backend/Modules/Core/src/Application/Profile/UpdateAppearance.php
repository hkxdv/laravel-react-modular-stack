<?php

declare(strict_types=1);

namespace Modules\Core\Application\Profile;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;

use function Foundry\Helpers\userId;

/**
 * Caso de uso: actualizar preferencias de apariencia del usuario.
 *
 * Persiste preferencias normalizadas y registra auditoría/actividad.
 */
final readonly class UpdateAppearance
{
    /**
     * Actualiza preferencias de apariencia del usuario.
     *
     * @param  StaffUser  $user  Usuario staff.
     * @param  mixed  $preferences  Preferencias (array recomendado).
     */
    public function handle(StaffUser $user, mixed $preferences): void
    {
        $normalized = [];
        if (is_array($preferences)) {
            foreach ($preferences as $k => $v) {
                $normalized[(string) $k] = $v;
            }
        }

        $uid = userId($user);

        if ($uid !== 'anonymous') {
            Cache::put(
                'user.'.$uid.'.appearance',
                $normalized,
                now()->addDays(30)
            );
        }

        Log::channel('domain_profile')->info('Apariencia actualizada', [
            'user_id' => $user->getAuthIdentifier(),
            'preferences' => $normalized,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('appearance_updated')
            ->withProperties([
                'preferences' => $normalized,
            ])
            ->log('Actualización de apariencia en el perfil de usuario');
    }
}
