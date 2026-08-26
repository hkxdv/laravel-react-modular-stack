<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Modules\Core\Application\Permissions\SyncCrossGuardPermissions;

#[\Illuminate\Console\Attributes\Description('Sincroniza roles y permisos entre los guards configurados')]
#[\Illuminate\Console\Attributes\Signature('permissions:sync-guards')]
final class SyncGuardPermissionsCommand extends Command
{
    /**
     * Ejecuta el comando de consola.
     */
    public function handle(): int
    {
        $this->info('Iniciando sincronización de roles y permisos entre guards...');

        try {
            resolve(SyncCrossGuardPermissions::class)->handle();

            $this->info('Sincronización completada exitosamente.');

            return Command::SUCCESS;
        } catch (Exception $exception) {
            $this->error('Error durante la sincronización: '.$exception->getMessage());

            return Command::FAILURE;
        }
    }
}
