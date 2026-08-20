<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

final class CoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sincronizando permisos entre guards...');
        Artisan::call('permissions:sync-guards');
    }
}
