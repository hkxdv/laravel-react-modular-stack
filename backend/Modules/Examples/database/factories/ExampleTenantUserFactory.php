<?php

declare(strict_types=1);

namespace Modules\Examples\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Examples\App\Models\ExampleTenantUser;

/**
 * Factory para ExampleTenantUser.
 *
 * @extends Factory<ExampleTenantUser>
 */
final class ExampleTenantUserFactory extends Factory
{
    protected $model = ExampleTenantUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];
    }
}
