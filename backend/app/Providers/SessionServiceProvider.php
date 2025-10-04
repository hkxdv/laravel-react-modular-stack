<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de servicios para la gestión de sesiones.
 *
 * Este proveedor personaliza el comportamiento del manejador de sesiones de base de datos
 * de Laravel para que funcione con la columna `staff_user_id` en lugar de la `user_id` estándar.
 */
class SessionServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de sesión personalizados.
     *
     * Aquí se extiende el manejador de sesiones de base de datos de Laravel para reemplazarlo
     * con nuestra implementación personalizada (CustomDatabaseSessionHandler).
     */
    public function register(): void
    {
        $this->app->resolving('session', function ($sessionManager) {
            $sessionManager->extend('database', function ($app, $config) {
                $table = $config['table'] ?? $app['config']['session.table'];
                $lifetime = $config['lifetime'] ?? $app['config']['session.lifetime'];
                $connection = $config['connection'] ?? $app['config']['session.connection'];

                return new CustomDatabaseSessionHandler(
                    $app['db']->connection($connection),
                    $table,
                    $lifetime,
                    $app
                );
            });
        });
    }

    /**
     * Arranca los servicios de sesión.
     *
     * Este método define un listener para el evento de login que se encarga de una
     * casuística específica para entornos de prueba que usan SQLite.
     */
    public function boot(): void
    {
        // Escucha el evento de login para sincronizar el 'user_id' en entornos SQLite.
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            // Esta lógica es una solución temporal para cuando se usa SQLite en pruebas.
            // Algunas partes de Laravel o paquetes de terceros pueden depender de la columna 'user_id',
            // y este listener asegura que se rellene después del login, aunque nuestro manejador
            // personalizado se centre en 'staff_user_id'.
            if ($event->user && DB::connection(config('session.connection'))->getDriverName() === 'sqlite') {
                DB::table(config('session.table'))
                    ->where('id', $this->app['session']->getId())
                    ->update(['user_id' => $event->user->id]);
            }
        });
    }
}

/**
 * Manejador de sesiones de base de datos personalizado.
 *
 * Sobrescribe el manejador por defecto de Laravel para utilizar la columna `staff_user_id`
 * para usuarios autenticados con el guard de staff. Esto permite que el sistema de sesiones
 * funcione correctamente con el modelo de usuarios del personal.
 */
class CustomDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Añade la información del usuario al payload de la sesión.
     *
     * @param  array  &$payload  El payload de la sesión, pasado por referencia.
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        if ($this->container->bound('auth')) {
            $userId = $this->userId();
            if ($userId) {
                // Determinar el tipo de usuario basado en el guard activo
                $currentGuard = $this->getCurrentGuard();

                if ($currentGuard === 'staff') {
                    $payload['staff_user_id'] = $userId;
                }

                // Mantener user_id para compatibilidad
                $payload['user_id'] = $userId;
            }
        }

        return $this;
    }

    /**
     * Realiza la actualización de una sesión existente en la base de datos.
     *
     * @param  string  $sessionId  El ID de la sesión a actualizar.
     * @param  string  $data  Los datos serializados de la sesión.
     * @return int El número de filas afectadas.
     */
    protected function performUpdate($sessionId, $data)
    {
        $updateData = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard === 'staff') {
                $updateData['staff_user_id'] = $userId;
            }

            // Mantener user_id para compatibilidad
            $updateData['user_id'] = $userId;
        }

        return $this->getQuery()->where('id', $sessionId)->update($updateData);
    }

    /**
     * Realiza la inserción de una nueva sesión en la base de datos.
     *
     * @param  string  $sessionId  El ID de la nueva sesión.
     * @param  string  $data  Los datos serializados de la sesión.
     * @return bool True si la inserción fue exitosa, false en caso contrario.
     */
    protected function performInsert($sessionId, $data)
    {
        $insertData = [
            'id' => $sessionId,
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($userId = $this->userId()) {
            $currentGuard = $this->getCurrentGuard();

            if ($currentGuard === 'staff') {
                $insertData['staff_user_id'] = $userId;
            }

            // Mantener user_id para compatibilidad
            $insertData['user_id'] = $userId;
        }

        return $this->getQuery()->insert($insertData);
    }

    /**
     * Obtiene el guard actualmente autenticado.
     */
    protected function getCurrentGuard(): ?string
    {
        $auth = $this->container->make('auth');

        // Verificar guard staff
        if ($auth->guard('staff')->check()) {
            return 'staff';
        }

        return null;
    }
}
