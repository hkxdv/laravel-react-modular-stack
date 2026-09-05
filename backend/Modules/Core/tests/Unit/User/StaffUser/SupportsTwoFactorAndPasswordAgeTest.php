<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\App\Models\StaffUser;
use Modules\Admin\Database\Factories\StaffUsersFactory;
use Modules\Core\Contracts\User\SupportsPasswordAge;
use Modules\Core\Contracts\User\SupportsTwoFactor;

uses(RefreshDatabase::class);

// ── AUTC-S1: StaffUser implements capability contracts with accessors ──

it('staff user implements both capability contracts', function (): void {
    // create() (no createOne) mantiene el union type en runtime para que el
    // instanceof sea una aserción real, no una tautología estática.
    $user = StaffUsersFactory::new()->create();

    expect($user)->toBeInstanceOf(SupportsTwoFactor::class)
        ->and($user)->toBeInstanceOf(SupportsPasswordAge::class);
});

it('two factor accessors map to real columns with correct types', function (): void {
    $user = StaffUsersFactory::new()->createOne([
        'two_factor_secret' => 'ENCRYPTED-SECRET',
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->twoFactorSecret())->toBeString()
        ->and($user->twoFactorConfirmedAt())->toBeInstanceOf(DateTimeImmutable::class)
        ->and($user->twoFactorEnabled())->toBeTrue()
        ->and($user->twoFactorPending())->toBeFalse();
});

it('two factor pending is true when a secret exists but confirmation is missing', function (): void {
    $user = StaffUsersFactory::new()->createOne([
        'two_factor_secret' => 'ENCRYPTED-SECRET',
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->twoFactorSecret())->toBeString()
        ->and($user->twoFactorConfirmedAt())->toBeNull()
        ->and($user->twoFactorEnabled())->toBeFalse()
        ->and($user->twoFactorPending())->toBeTrue();
});

it('two factor accessors are null and false when no secret is set', function (): void {
    $user = StaffUsersFactory::new()->createOne();

    expect($user->twoFactorSecret())->toBeNull()
        ->and($user->twoFactorConfirmedAt())->toBeNull()
        ->and($user->twoFactorEnabled())->toBeFalse()
        ->and($user->twoFactorPending())->toBeFalse();
});

it('password changed at returns an immutable date when set', function (): void {
    $user = StaffUsersFactory::new()->createOne([
        'password_changed_at' => now()->subDays(30),
    ]);

    expect($user->passwordChangedAt())->toBeInstanceOf(DateTimeImmutable::class);
});
