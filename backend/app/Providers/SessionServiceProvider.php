<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use UnitEnum;

/**
 * Proveedor de servicios para la gestión de sesiones.
 *
 * Este proveedor personaliza el comportamiento del manejador de sesiones de base de datos
 * de Laravel para que funcione con columnas polimórficas (authenticatable_type + authenticatable_id)
 * en lugar de la columna user_id estándar.
 */
final class SessionServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de sesión personalizados.
     */
    public function register(): void
    {
        $this->app->resolving(
            'session',
            function (SessionManager $sessionManager): void {
                $sessionManager->extend(
                    'database',
                    function (Container $app): CustomDatabaseSessionHandler {
                        /** @var Repository $configRepo */
                        $configRepo = $app->make(Repository::class);

                        /** @var array<string, mixed> $config */
                        $config = (array) $configRepo->get('session', []);

                        $tableValue = $config['table']
                            ?? $configRepo->get('session.table');
                        $table = is_string($tableValue)
                            ? $tableValue : 'sessions';
                        $lifetimeValue = $config['lifetime']
                            ?? $configRepo->get('session.lifetime');
                        $lifetime = is_int($lifetimeValue)
                            ? $lifetimeValue
                            : (is_string($lifetimeValue)
                                ? (int) $lifetimeValue : 120
                            );
                        $connectionValue = $config['connection']
                            ?? $configRepo->get('session.connection');

                        $connection = $connectionValue instanceof UnitEnum || is_string($connectionValue)
                            ? $connectionValue : null;

                        /** @var DatabaseManager $db */
                        $db = $app->make(DatabaseManager::class);

                        /** @var \Illuminate\Database\ConnectionInterface $connectionInstance */
                        $connectionInstance = $db->connection($connection);

                        return new CustomDatabaseSessionHandler(
                            $connectionInstance,
                            $table,
                            $lifetime,
                            $app
                        );
                    }
                );
            }
        );
    }

    /**
     * Arranca los servicios de sesión.
     */
    public function boot(): void
    {
        // El contenedor de la aplicación está disponible en los ServiceProviders
        // y no es nulo en tiempo de ejecución.

        // Escucha el evento de login para sincronizar columnas polimórficas en entornos SQLite.
        Event::listen(
            Login::class,
            function (Login $event): void {
                // Esta lógica es una solución temporal para cuando se usa SQLite en pruebas.
                // Algunas partes de Laravel o paquetes de terceros pueden depender de columnas específicas,
                // y este listener asegura que se rellenen después del login.

                /** @var Repository $configRepo */
                $configRepo = $this->app->make(Repository::class);

                $sessionConnectionValue = $configRepo->get(
                    'session.connection'
                );
                $sessionConnection = $sessionConnectionValue
                    instanceof UnitEnum || is_string($sessionConnectionValue)
                    ? $sessionConnectionValue
                    : null;

                $driver = DB::connection($sessionConnection)->getDriverName();

                /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
                $user = $event->user;

                if ($driver === 'sqlite') {
                    $tableValue = $configRepo->get('session.table');
                    $table = is_string($tableValue) ? $tableValue : 'sessions';

                    // Determinar el morph class a partir del guard del usuario
                    $morphClass = $user instanceof \Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser
                        ? $user->getMorphClass()
                        : 'staff-user';

                    DB::table($table)
                        ->where(
                            'id',
                            $this->app->make(SessionManager::class)->getId()
                        )
                        ->update([
                            'authenticatable_type' => $morphClass,
                            'authenticatable_id' => $user->getAuthIdentifier(),
                        ]);
                }
            }
        );
    }
}

/**
 * Manejador de sesiones de base de datos personalizado.
 *
 * Sobrescribe el manejador por defecto de Laravel para utilizar la columna `staff_user_id`
 * para usuarios autenticados con el guard de staff. Esto permite que el sistema de sesiones
 * funcione correctamente con el modelo de usuarios del personal.
 */
final class CustomDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Añade la información del usuario al payload de la sesión.
     *
     * @param  array<string, mixed>  $payload
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        if ($this->container && $this->container->bound('auth')) {
            $userId = $this->userId();
            if ($userId) {
                $currentGuard = $this->getCurrentGuard();

                if ($currentGuard !== null) {
                    $payload['authenticatable_type'] = $this->resolveMorphClass($currentGuard);
                    $payload['authenticatable_id'] = $userId;
                }
            }
        }

        return $this;
    }

    /**
     * Realiza la actualización de una sesión existente en la base de datos.
     *
     * @param  string  $sessionId
     * @param  array<string, mixed>  $payload
     */
    protected function performUpdate($sessionId, $payload)
    {
        // Partir del payload por defecto generado por el manejador base
        $updateData = $payload;
        $updateData['ip_address'] = request()->ip();
        $updateData['user_agent'] = request()->userAgent();

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard !== null) {
                $updateData['authenticatable_type'] = $this->resolveMorphClass($currentGuard);
                $updateData['authenticatable_id'] = $userId;
            }
        }

        return $this->getQuery()->where('id', $sessionId)->update($updateData);
    }

    /**
     * Realiza la inserción de una nueva sesión en la base de datos.
     *
     * @param  string  $sessionId
     * @param  array<string, mixed>  $payload
     */
    protected function performInsert($sessionId, $payload)
    {
        // Partir del payload por defecto generado por el manejador base
        $insertData = $payload;
        $insertData['id'] = $sessionId;
        $insertData['ip_address'] = request()->ip();
        $insertData['user_agent'] = request()->userAgent();

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard !== null) {
                $insertData['authenticatable_type'] = $this->resolveMorphClass($currentGuard);
                $insertData['authenticatable_id'] = $userId;
            }
        }

        try {
            return $this->getQuery()->insert($insertData);
        } catch (QueryException) {
            $this->performUpdate($sessionId, $insertData);

            return null;
        }
    }

    /**
     * Resuelve la clase morph a partir del nombre del guard.
     *
     * Convención: '{guard}-user' (ej. 'staff' → 'staff-user', 'tenant' → 'tenant-user').
     */
    private function resolveMorphClass(string $guardName): string
    {
        return $guardName.'-user';
    }

    /**
     * Obtiene el guard actualmente autenticado.
     */
    private function getCurrentGuard(): ?string
    {
        if (! $this->container) {
            return null;
        }

        $auth = $this->container->make(
            \Illuminate\Contracts\Auth\Factory::class
        );

        /** @var array<string, mixed> $guardsConfig */
        $guardsConfig = config('core.guards', []);
        $guards = array_keys($guardsConfig);
        foreach ($guards as $guardName) {
            if ($auth->guard((string) $guardName)->check()) {
                return (string) $guardName;
            }
        }

        return null;
    }
}
