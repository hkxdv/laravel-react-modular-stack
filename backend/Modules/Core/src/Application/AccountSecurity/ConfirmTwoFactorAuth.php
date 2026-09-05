<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\ConfirmTwoFactorAuthInterface;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Services\TwoFactorCodeVerifier;

use function Foundry\Helpers\configInt;

/**
 * Caso de uso: confirmar el código de 2FA (TOTP) del usuario.
 *
 * Verifica el código TOTP usando el secreto almacenado y marca 2FA como
 * confirmado; registra auditoría y actividad.
 */
final readonly class ConfirmTwoFactorAuth implements ConfirmTwoFactorAuthInterface
{
    public function __construct(
        private TwoFactorCodeVerifier $verifier,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(AbstractDomainUser $user, string $code): bool
    {
        $secretEncrypted = $user->getAttribute('two_factor_secret');
        if (! is_string($secretEncrypted) || $secretEncrypted === '') {
            return false;
        }

        $secret = Crypt::decryptString($secretEncrypted);
        if (! $this->verifier->verify(
            $secret,
            $code,
            configInt(
                'core.guards.'.$user->getAuthGuard().'.two_factor.totp_window',
                configInt('security.two_factor.'.$user->getAuthGuard().'.totp_window', 30)
            )
        )) {
            return false;
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        Log::channel('domain_audit')->info('2FA confirmado', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->event('two_factor_confirmed')
            ->log('Confirmación de 2FA');

        return true;
    }
}
