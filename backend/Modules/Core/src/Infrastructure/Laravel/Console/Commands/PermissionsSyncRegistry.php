<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\PermissionRegistryInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sincroniza permisos granulares desde los registros de cada módulo.
 */
final class PermissionsSyncRegistry extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $signature = 'permissions:sync-registry';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Crea/actualiza permisos granulares desde los PermissionRegistry de cada módulo';

    /**
     * {@inheritDoc}
     */
    public function handle(): int
    {
        $registries = app()->tagged('permission-registry');

        $count = 0;

        foreach ($registries as $registry) {
            if (! $registry instanceof PermissionRegistryInterface) {
                continue;
            }

            foreach ($registry->permissions() as $perm) {
                Permission::query()->firstOrCreate([
                    'name' => $perm['name'],
                    'guard_name' => $perm['guard'],
                ], [
                    'description' => $perm['description'],
                ]);

                $count++;
            }
        }

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(sprintf('Sincronizados %d permisos desde los registros de módulos.', $count));

        return Command::SUCCESS;
    }
}
