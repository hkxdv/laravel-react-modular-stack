<?php

declare(strict_types=1);

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;

final class AdminDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            // TestStaffUsersSeeder::class, // Uncomment for test data
        ]);
    }
}
