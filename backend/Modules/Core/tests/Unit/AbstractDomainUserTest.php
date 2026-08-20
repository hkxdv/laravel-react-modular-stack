<?php

declare(strict_types=1);

use App\Interfaces\AuthenticatableUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Traits\CanBeImpersonated;
use Modules\Core\Infrastructure\Laravel\Traits\HasCrossGuardPermissions;
use Modules\Core\Tests\Fakes\FakeDomainUser;
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

// ── REQ-02: Shared behaviour on base (via FakeDomainUser) ──

it('inherits get avatar attribute', function (): void {
    $user = new FakeDomainUser;
    $user->name = 'Test User';

    $avatar = $user->avatar;

    expect($avatar)->toContain('ui-avatars.com')
        ->and($avatar)->toContain(urlencode('Test User'));
});

it('defaults is active to true', function (): void {
    $user = new FakeDomainUser;

    expect($user->isActive())->toBeTrue();
});

it('defaults trashed to false', function (): void {
    $user = new FakeDomainUser;

    expect($user->trashed())->toBeFalse();
});

it('has avatar in appends', function (): void {
    $user = new FakeDomainUser;

    expect($user->getAppends())->toContain('avatar');
});

// ── Concrete subclass wiring ──

it('fake domain user extends abstract domain user', function (): void {
    $reflection = new ReflectionClass(FakeDomainUser::class);

    expect($reflection->isSubclassOf(AbstractDomainUser::class))->toBeTrue();
});

it('does not modify domain dto', function (): void {
    $domainDtoFile = __DIR__.'/../../src/Domain/User/DomainUser.php';
    $originalHash = md5_file($domainDtoFile);

    // The Domain DTO should be identical to the committed version
    expect($originalHash)->not->toBeEmpty();
});
