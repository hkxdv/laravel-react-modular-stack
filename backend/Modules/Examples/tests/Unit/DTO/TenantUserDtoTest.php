<?php

declare(strict_types=1);

use Modules\Examples\App\DTO\TenantUserDto;
use PHPUnit\Framework\Assert;

it('creates a TenantUserDto with default empty roles and permissions', function (): void {
    $dto = new TenantUserDto(
        id: 10,
        name: 'Acme Corp',
        email: 'acme@example.com',
        user_type: 'tenant',
    );

    Assert::assertInstanceOf(TenantUserDto::class, $dto);

    $serialized = $dto->jsonSerialize();

    expect($serialized)->toBe([
        'id' => 10,
        'name' => 'Acme Corp',
        'email' => 'acme@example.com',
        'user_type' => 'tenant',
        'roles' => [],
        'permissions' => [],
        'avatar' => null,
        'email_verified_at' => null,
    ])->and(array_keys($serialized))->toBe([
        'id', 'name', 'email', 'user_type', 'roles', 'permissions', 'avatar', 'email_verified_at',
    ]);
});

it('serializes null avatar and email_verified_at as null', function (): void {
    $dto = new TenantUserDto(
        id: 11,
        name: 'Beta Inc',
        email: 'beta@example.com',
        user_type: 'tenant',
        roles: [],
        permissions: [],
    );

    expect($dto->jsonSerialize()['avatar'])->toBeNull()
        ->and($dto->jsonSerialize()['email_verified_at'])->toBeNull();
});
