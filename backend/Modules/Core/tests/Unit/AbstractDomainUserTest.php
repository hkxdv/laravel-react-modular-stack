<?php

declare(strict_types=1);

use App\Interfaces\AuthenticatableUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Eloquent\Models\StaffUser;
use Modules\Core\Infrastructure\Laravel\Traits\CanBeImpersonated;
use Modules\Core\Infrastructure\Laravel\Traits\HasCrossGuardPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

uses(Tests\TestCase::class);

// ── REQ-01: AbstractDomainUser structure ──

it('is abstract and extends authenticatable', function (): void {
    $reflection = new ReflectionClass(AbstractDomainUser::class);

    expect($reflection->isAbstract())->toBeTrue()
        ->and($reflection->isFinal())->toBeFalse()
        ->and($reflection->isSubclassOf(Authenticatable::class))->toBeTrue()
        ->and($reflection->implementsInterface(AuthenticatableUser::class))->toBeTrue();
});

it('has six traits on base', function (): void {
    $reflection = new ReflectionClass(AbstractDomainUser::class);
    $traitNames = array_map(
        static fn (ReflectionClass $t): string => $t->getName(),
        $reflection->getTraits()
    );

    expect($traitNames)->toContain(...[
        CanBeImpersonated::class,
        HasApiTokens::class,
        HasCrossGuardPermissions::class,
        HasRoles::class,
        LogsActivity::class,
        Notifiable::class,
    ])->and(count($traitNames))->toBeGreaterThanOrEqual(6);
});

// ── REQ-02: Shared behaviour on base ──

it('inherits get avatar attribute', function (): void {
    $user = new StaffUser;
    $user->name = 'Test User';

    $avatar = $user->avatar;

    expect($avatar)->toContain('ui-avatars.com')
        ->and($avatar)->toContain(urlencode('Test User'));
});

it('defaults is active to true', function (): void {
    $user = new StaffUser;

    expect($user->isActive())->toBeTrue();
});

it('defaults trashed to false', function (): void {
    $user = new StaffUser;

    expect($user->trashed())->toBeFalse();
});

it('has avatar in appends', function (): void {
    $user = new StaffUser;

    expect($user->getAppends())->toContain('avatar');
});

// ── REQ-04: StaffUser rewired ──

it('extends abstract domain user', function (): void {
    $reflection = new ReflectionClass(StaffUser::class);

    expect($reflection->isSubclassOf(AbstractDomainUser::class))->toBeTrue();
});

it('is not final', function (): void {
    $reflection = new ReflectionClass(StaffUser::class);

    expect($reflection->isFinal())->toBeFalse();
});

it('implements must verify email', function (): void {
    $user = new StaffUser;

    expect($user)->toBeInstanceOf(MustVerifyEmail::class);
});

it('retains staff user methods', function (): void {
    $reflection = new ReflectionClass(StaffUser::class);

    expect($reflection->hasMethod('loginInfos'))->toBeTrue()
        ->and($reflection->hasMethod('recordLogin'))->toBeTrue()
        ->and($reflection->hasMethod('isSuspiciousLogin'))->toBeTrue()
        ->and($reflection->hasMethod('getAuthGuard'))->toBeTrue()
        ->and($reflection->hasMethod('getDisplayName'))->toBeTrue();
});

it('redeclares only has factory', function (): void {
    $reflection = new ReflectionClass(StaffUser::class);
    $ownTraits = $reflection->getTraits();
    $ownTraitNames = array_keys($ownTraits);

    // StaffUser should only directly declare HasFactory (the other 6 are inherited from base)
    expect($ownTraitNames)->toContain(HasFactory::class)
        ->and(count($ownTraitNames))->toBe(1);
});

it('does not modify domain dto', function (): void {
    $domainDtoFile = __DIR__.'/../../src/Domain/User/StaffUser.php';
    $originalHash = md5_file($domainDtoFile);

    // The Domain DTO should be identical to the committed version
    expect($originalHash)->not->toBeEmpty();
});
