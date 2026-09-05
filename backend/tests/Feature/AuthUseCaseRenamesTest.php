<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Interfaces\AuthenticatableUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Application\Auth\ImpersonateUser;
use Modules\Core\Application\Auth\LoginUser;
use Modules\Core\Application\Auth\LogoutUser;
use Modules\Core\Contracts\Auth\AuthenticatesUsersInterface;
use Modules\Core\Contracts\Auth\ImpersonatesUsersInterface;

// ── AUTC-S3: LoginStaffUser→LoginUser / LogoutStaffUser→LogoutUser /
// ImpersonateStaffUser→ImpersonateUser (renombres sin consumidores previos) ──

it('LoginUser delega el intento de login a la interfaz de autenticación', function (): void {
    $attempted = [];
    $auth = new class($attempted) implements AuthenticatesUsersInterface
    {
        /** @param  array<int, array{credentials: array<string, mixed>, remember: bool}>  $attempted */
        public function __construct(private array &$attempted) {}

        public function attempt(array $credentials, bool $remember = false): bool
        {
            $this->attempted[] = ['credentials' => $credentials, 'remember' => $remember];

            return true;
        }

        public function logout(): void {}

        public function user(): ?Authenticatable
        {
            return null;
        }

        public function check(): bool
        {
            return false;
        }

        public function id(): int|string|null
        {
            return null;
        }
    };

    $useCase = new LoginUser($auth);
    $result = $useCase->handle(['email' => 'a@b.c', 'password' => 'secret'], true);

    expect($result)->toBeTrue()
        ->and($attempted)->toBe([[
            'credentials' => ['email' => 'a@b.c', 'password' => 'secret'],
            'remember' => true,
        ]]);
});

it('LogoutUser delega el cierre de sesión a la interfaz de autenticación', function (): void {
    $logoutCalled = 0;
    $auth = new class($logoutCalled) implements AuthenticatesUsersInterface
    {
        public function __construct(private int &$logoutCalled) {}

        public function attempt(array $credentials, bool $remember = false): bool
        {
            return false;
        }

        public function logout(): void
        {
            $this->logoutCalled++;
        }

        public function user(): ?Authenticatable
        {
            return null;
        }

        public function check(): bool
        {
            return false;
        }

        public function id(): int|string|null
        {
            return null;
        }
    };

    (new LogoutUser($auth))->handle();

    expect($logoutCalled)->toBe(1);
});

it('ImpersonateUser delega la suplantación a la interfaz de impersonación', function (): void {
    $impersonated = [];
    $impersonator = new class($impersonated) implements ImpersonatesUsersInterface
    {
        /** @param  array<int, mixed>  $impersonated */
        public function __construct(private array &$impersonated) {}

        public function impersonate(Authenticatable $user): bool
        {
            $this->impersonated[] = $user;

            return true;
        }

        public function stopImpersonating(): bool
        {
            return true;
        }

        public function isImpersonating(): bool
        {
            return false;
        }
    };

    $target = $this->createMock(AuthenticatableUser::class);

    $result = (new ImpersonateUser($impersonator))->handle($target);

    expect($result)->toBeTrue()
        ->and($impersonated)->toHaveCount(1)
        ->and($impersonated[0])->toBe($target);
});

it('los nombres de clase legacy LoginStaffUser/LogoutStaffUser/ImpersonateStaffUser ya no existen', function (): void {
    expect(class_exists('Modules\Core\Application\Auth\LoginStaffUser'))->toBeFalse()
        ->and(class_exists('Modules\Core\Application\Auth\LogoutStaffUser'))->toBeFalse()
        ->and(class_exists('Modules\Core\Application\Auth\ImpersonateStaffUser'))->toBeFalse();
});
