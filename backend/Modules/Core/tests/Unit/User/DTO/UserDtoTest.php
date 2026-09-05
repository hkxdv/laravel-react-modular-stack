<?php

declare(strict_types=1);

use Modules\Core\Domain\User\DTO\UserDto;
use PHPUnit\Framework\Assert;

it('creates a base UserDto and serializes exactly the 4-key shape', function (): void {
    $dto = new UserDto(
        id: 42,
        name: 'Alan Turing',
        email: 'alan@example.com',
        user_type: 'staff',
    );

    Assert::assertInstanceOf(UserDto::class, $dto);

    expect($dto->jsonSerialize())->toBe([
        'id' => 42,
        'name' => 'Alan Turing',
        'email' => 'alan@example.com',
        'user_type' => 'staff',
    ])->and(array_keys($dto->jsonSerialize()))->toBe(['id', 'name', 'email', 'user_type']);
});
