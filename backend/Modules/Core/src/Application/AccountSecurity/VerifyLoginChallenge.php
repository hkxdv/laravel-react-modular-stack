<?php

declare(strict_types=1);

namespace Modules\Core\Application\AccountSecurity;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Modules\Core\Contracts\AccountSecurity\VerifyLoginChallengeInterface;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Services\TwoFactorCodeVerifier;

/**
 * Caso de uso: completar el challenge de 2FA en el login (TOTP o recovery code).
 *
 * Acepta un TOTP válido contra el secreto almacenado, o un recovery code
 * de un solo uso (se consume al canjearlo), y establece la sesión del guard.
 */
final readonly class VerifyLoginChallenge implements VerifyLoginChallengeInterface
{
    public function __construct(
        private TwoFactorCodeVerifier $verifier,
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function handle(AbstractDomainUser $user, string $code, bool $remember = false): bool
    {
        $secretEncrypted = $user->getAttribute('two_factor_secret');
        $isSecretValid = is_string($secretEncrypted) && $secretEncrypted !== '';
        $isRecoveryValid = $this->redeemRecoveryCode($user, $code);

        if (! $isSecretValid && ! $isRecoveryValid) {
            return false;
        }

        if ($isSecretValid && ! $isRecoveryValid) {
            $secret = Crypt::decryptString($secretEncrypted);
            if (! $this->verifier->verify($secret, $code)) {
                return false;
            }
        }

        $guard = Auth::guard('staff');
        if (! $guard->loginUsingId($user->getAuthIdentifier(), $remember)) {
            return false;
        }

        Log::channel('domain_audit')->info('2FA challenge superado', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        return true;
    }

    /**
     * Canjea un recovery code de un solo uso si coincide con el código enviado.
     */
    private function redeemRecoveryCode(AbstractDomainUser $user, string $code): bool
    {
        $stored = $user->getAttribute('two_factor_recovery_codes');
        if (! is_string($stored) || $stored === '') {
            return false;
        }

        $codes = json_decode(Crypt::decryptString($stored), true);
        if (! is_array($codes)) {
            return false;
        }

        $normalized = mb_strtoupper(preg_replace('/[\s-]+/', '', $code) ?? '');
        if ($normalized === '') {
            return false;
        }

        $remaining = array_values(array_filter(
            $codes,
            static fn (mixed $candidate): bool => ! is_string($candidate) || $candidate !== $normalized,
        ));

        if (count($remaining) === count($codes)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(
                json_encode($remaining, JSON_THROW_ON_ERROR)
            ),
        ])->save();

        Log::channel('domain_audit')->info('Recovery code canjeado (2FA login)', [
            'user_id' => $user->getAuthIdentifier(),
        ]);

        return true;
    }
}
