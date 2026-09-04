<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Application\Auth\AbstractLoginRequest;
use Modules\Core\Contracts\AccountSecurity\VerifyLoginChallengeInterface;
use Modules\Core\Infrastructure\Laravel\Services\TwoFactorCodeVerifier;
use Symfony\Component\HttpFoundation\Response;

uses(RefreshDatabase::class);

/**
 * GAP-4: en suite completa las rutas de módulos no se re-registran en el
 * re-boot; este helper salta el test cuando la ruta de login no está disponible.
 */
function skipIfNoLoginRoute(): void
{
    throw_unless(Route::has('login'), \PHPUnit\Framework\SkippedTestSuiteError::class, 'GAP-4: rutas de módulos no disponibles en re-boot de suite');
}

/**
 * TOTP RFC-6238 para un secreto base32 conocido (espejo del verifier de Core).
 */
function totpForSecret(string $base32Secret, int $stepOffset = 0): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $map = array_flip(mb_str_split($alphabet));
    $binary = '';
    foreach (mb_str_split(mb_rtrim(mb_strtoupper($base32Secret), '=')) as $char) {
        $binary .= mb_str_pad(decbin($map[$char]), 5, '0', STR_PAD_LEFT);
    }

    $secret = '';
    foreach (mb_str_split($binary, 8) as $chunk) {
        if (mb_strlen($chunk) < 8) {
            break;
        }

        $secret .= chr((int) bindec($chunk));
    }

    $counter = (int) floor(\Illuminate\Support\Facades\Date::now()->getTimestamp() / 30) + $stepOffset;
    $hash = hash_hmac('sha1', pack('N*', 0, $counter), $secret, true);
    $offset = ord(mb_substr($hash, -1)) & 0x0F;
    $value = unpack('N', mb_substr($hash, $offset, 4));
    $rawValue = is_array($value) ? ($value[1] ?? null) : null;
    $raw = is_numeric($rawValue) ? (int) $rawValue : 0;
    $code = ($raw & 0x7FFFFFFF) % 1000000;

    return mb_str_pad((string) $code, 6, '0', STR_PAD_LEFT);
}

/**
 * Workaround GAP-4: el flujo HTTP completo (login -> challenge -> TOTP) solo es
 * viable cuando las rutas de módulos existen en el proceso. En suite completa
 * las rutas de módulos no se re-registran (bug laravel-modules + Laravel 13 +
 * Pest re-boot), así que este test se salta automáticamente si la ruta de login
 * no está disponible y se ejecuta aislado:
 *   vendor/bin/pest Modules/Core/tests/Feature/TwoFactorAuthTest.php --filter="flujo completo"
 */
it('flujo completo: login con 2FA -> challenge -> TOTP válido -> autentica', function (): void {
    skipIfNoLoginRoute();

    /** @var \Modules\Admin\App\Models\StaffUser $user */
    $user = StaffUsersFactory::new()->create([
        'email' => sprintf('staff-%s@laravel.com', bin2hex(random_bytes(4))),
        'two_factor_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => Crypt::encryptString(json_encode(['ABCDEF1234'], JSON_THROW_ON_ERROR)),
        'two_factor_confirmed_at' => now(),
    ]);

    // 1. Login: credenciales válidas + 2FA confirmado -> NO autentica, redirige a challenge.
    $login = $this->post('/internal/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $login->assertRedirect(route('security.two-factor-challenge'));
    $this->assertGuest('staff');
    expect(session(AbstractLoginRequest::TWO_FACTOR_PENDING_SESSION_KEY))->toBe($user->getKey());

    // 2. Challenge con TOTP válido -> autentica + pending limpio.
    $challenge = $this->post('/two-factor-challenge', [
        'code' => totpForSecret('JBSWY3DPEHPK3PXP'),
    ]);

    $challenge->assertRedirect();
    $this->assertAuthenticatedAs($user, 'staff');
    expect(session(AbstractLoginRequest::TWO_FACTOR_PENDING_SESSION_KEY))->toBeNull();
});

it('challenge rechaza un TOTP inválido (verifier directo)', function (): void {
    $verifier = resolve(TwoFactorCodeVerifier::class);

    // Secreto base32 conocido: JBSWY3DPEHPK3PXP
    $secret = 'JBSWY3DPEHPK3PXP';

    // Inválido -> false.
    expect($verifier->verify($secret, '000000'))->toBeFalse();

    $valid = array_any([-1, 0, 1], fn(int $offset) => $verifier->verify($secret, totpForSecret($secret, $offset)));

    expect($valid)->toBeTrue();
});

it('recovery code se canjea una sola vez (use-case VerifyLoginChallenge)', function (): void {
    /** @var \Modules\Admin\App\Models\StaffUser $user */
    $user = StaffUsersFactory::new()->create([
        'email' => sprintf('staff-%s@laravel.com', bin2hex(random_bytes(4))),
        'two_factor_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
        'two_factor_recovery_codes' => Crypt::encryptString(json_encode(['ABCDEF1234'], JSON_THROW_ON_ERROR)),
        'two_factor_confirmed_at' => now(),
    ]);

    /** @var VerifyLoginChallengeInterface $challenge */
    $challenge = resolve(VerifyLoginChallengeInterface::class);

    // Primer uso: ok y autentica en guard staff.
    $ok = $challenge->handle($user, 'ABCDEF1234');
    expect($ok)->toBeTrue()
        ->and(\Illuminate\Support\Facades\Auth::guard('staff')->check())->toBeTrue();

    // Cerrar sesión y reintentar el MISMO código (ya consumido).
    \Illuminate\Support\Facades\Auth::guard('staff')->logout();

    $again = $challenge->handle($user, 'ABCDEF1234');
    expect($again)->toBeFalse()
        ->and(\Illuminate\Support\Facades\Auth::guard('staff')->check())->toBeFalse();
});

it('middleware 2fa: policy true redirige a security.edit sin 2FA confirmado', function (): void {
    config()->set('core.guards.staff.two_factor_required', true);

    /** @var \Modules\Admin\App\Models\StaffUser $user */
    $user = StaffUsersFactory::new()->create([
        'email' => sprintf('staff-%s@laravel.com', bin2hex(random_bytes(4))),
    ]);
    \Illuminate\Support\Facades\Auth::guard('staff')->setUser($user);

    $request = \Illuminate\Http\Request::create('/internal/staff/dashboard');
    $response = resolve(\App\Http\Middleware\TwoFactorAuthentication::class)
        ->handle($request, fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toContain('internal/staff/security');
});

it('middleware 2fa: policy false no bloquea', function (): void {
    config()->set('core.guards.staff.two_factor_required', false);

    /** @var \Modules\Admin\App\Models\StaffUser $user */
    $user = StaffUsersFactory::new()->create([
        'email' => sprintf('staff-%s@laravel.com', bin2hex(random_bytes(4))),
    ]);
    \Illuminate\Support\Facades\Auth::guard('staff')->setUser($user);

    $request = \Illuminate\Http\Request::create('/internal/staff/dashboard');
    $response = resolve(\App\Http\Middleware\TwoFactorAuthentication::class)
        ->handle($request, fn (): Response => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});
