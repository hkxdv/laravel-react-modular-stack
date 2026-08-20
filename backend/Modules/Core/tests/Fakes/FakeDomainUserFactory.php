<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para FakeDomainUser (tests de Core).
 *
 * @extends Factory<FakeDomainUser>
 */
final class FakeDomainUserFactory extends Factory
{
    protected $model = FakeDomainUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
}
