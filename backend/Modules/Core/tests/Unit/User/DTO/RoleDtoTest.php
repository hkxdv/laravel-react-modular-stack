<?php

declare(strict_types=1);

use Modules\Core\Domain\User\DTO\RoleDto;
use PHPUnit\Framework\Assert;

it('creates a RoleDto with a null description', function (): void {
    $dto = new RoleDto(id: 1, name: 'viewer');

    Assert::assertInstanceOf(RoleDto::class, $dto);

    expect($dto->jsonSerialize())->toBe([
        'id' => 1,
        'name' => 'viewer',
        'description' => null,
    ]);
});

it('creates a RoleDto with a description', function (): void {
    $dto = new RoleDto(id: 2, name: 'admin', description: 'Admin role');

    expect($dto->jsonSerialize()['description'])->toBe('Admin role')
        ->and(array_keys($dto->jsonSerialize()))->toBe(['id', 'name', 'description']);
});
