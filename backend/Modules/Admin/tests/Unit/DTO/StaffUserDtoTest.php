<?php

declare(strict_types=1);

use Modules\Admin\App\DTO\StaffUserDto;
use Modules\Core\Domain\User\DTO\RoleDto;
use PHPUnit\Framework\Assert;

it('creates a StaffUserDto with all fields and serializes the 8-key shape', function (): void {
    $role = new RoleDto(id: 1, name: 'admin', description: 'Administrator');

    $dto = new StaffUserDto(
        id: 1,
        name: 'Ada Lovelace',
        email: 'ada@example.com',
        user_type: 'staff',
        roles: [$role],
        permissions: ['users.view'],
        email_verified_at: '2026-01-01T10:00:00+00:00',
        avatar: 'https://example.com/avatar.png',
    );

    Assert::assertInstanceOf(StaffUserDto::class, $dto);

    $serialized = $dto->jsonSerialize();

    expect($serialized)->toBe([
        'id' => 1,
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'email_verified_at' => '2026-01-01T10:00:00+00:00',
        'user_type' => 'staff',
        'roles' => [$role],
        'permissions' => ['users.view'],
        'avatar' => 'https://example.com/avatar.png',
    ])->and(array_keys($serialized))->toBe([
        'id', 'name', 'email', 'email_verified_at', 'user_type', 'roles', 'permissions', 'avatar',
    ]);
});

it('serializes a null avatar as null', function (): void {
    $dto = new StaffUserDto(
        id: 2,
        name: 'Grace Hopper',
        email: 'grace@example.com',
        user_type: 'staff',
        roles: [],
        permissions: [],
    );

    expect($dto->jsonSerialize()['avatar'])->toBeNull();
});

it('serializes roles as an array of RoleDto instances', function (): void {
    $role = new RoleDto(id: 2, name: 'editor');
    $dto = new StaffUserDto(
        id: 3,
        name: 'Linus Torvalds',
        email: 'linus@example.com',
        user_type: 'staff',
        roles: [$role],
        permissions: [],
    );

    $roles = $dto->jsonSerialize()['roles'];

    Assert::assertInstanceOf(RoleDto::class, $roles[0]);
});
