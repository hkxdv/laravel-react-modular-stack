<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\ApiResponseFormatterInterface;
use App\Services\ApiResponseService;
use App\Services\JsonbQueryService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Modules\Admin\App\Models\StaffUser;
use Modules\Core\Contracts\AccountSecurity\LoginAttemptInterface;
use Modules\Examples\App\Models\ExampleTenantUser;

/**
 * Proveedor de servicios principal de la aplicación
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los servicios de la aplicación en el contenedor de dependencias.
     */
    public function register(): void
    {
        /** @var Application $app */
        $app = $this->app;

        // Establece una ruta personalizada para la base de datos.
        $app->useDatabasePath(base_path('../database'));

        // Registra Telescope solo en entornos de no producción para depuración.
        if (! $this->app->environment('production')) {
            $this->app->register(
                \Laravel\Telescope\TelescopeServiceProvider::class
            );
            $this->app->register(TelescopeServiceProvider::class);
        }

        // Registrar las interfaces del sistema con sus implementaciones concretas.
        $this->app->singleton(
            ApiResponseFormatterInterface::class,
            ApiResponseService::class
        );
        $this->app->singleton(JsonbQueryService::class);
    }

    /**
     * Arranca los servicios de la aplicación después de que se hayan registrado.
     */
    public function boot(): void
    {
        // Register morph map for Spatie permission pivot tables.
        Relation::morphMap([
            'staff-user' => StaffUser::class,
            'tenant-user' => ExampleTenantUser::class,
        ]);

        $this->bootstrapPasskeys();
    }

    /**
     * Configura passkeys (laravel/passkeys standalone) para el guard staff.
     *
     * - Fija el modelo de usuario (el paquete usa BelongsTo por user_id, no polimórfico).
     * - Autoriza el login con passkey solo tras los mismos invariantes de seguridad
     *   que el login por contraseña: cuenta activa y blocklist/attempts por IP.
     *   El hook corre ANTES de que el paquete establezca la sesión.
     */
    private function bootstrapPasskeys(): void
    {
        Passkeys::useUserModel(StaffUser::class);

        Passkeys::authorizeLoginUsing(
            function (Request $request, Authenticatable $user, Passkey $passkey): bool {
                $isAuthorized = true;

                if (! $user instanceof StaffUser) {
                    Log::warning('Passkey login para modelo no staff rechazado', [
                        'user_type' => $user::class,
                    ]);

                    $isAuthorized = false;
                }

                if ($isAuthorized && ! $this->isUserActive($user)) {
                    Log::warning('Passkey login de cuenta inactiva rechazado', [
                        'user_id' => $user->getAuthIdentifier(),
                        'ip' => $request->ip(),
                    ]);

                    $isAuthorized = false;
                }

                $ip = $request->ip() ?? '';
                $attempts = resolve(LoginAttemptInterface::class);

                if ($isAuthorized && $attempts->isIpBlocked($ip)) {
                    Log::warning('Passkey login bloqueado por IP en lista negra.', [
                        'user_id' => $user->getAuthIdentifier(),
                        'ip' => $ip,
                    ]);

                    $isAuthorized = false;
                }

                return $isAuthorized;
            }
        );
    }

    /**
     * Replica la semántica de AbstractLoginRequest::isUserActive sin acoplarse
     * a columnas inexistentes (staff_users no tiene active/status hoy).
     */
    private function isUserActive(Model $user): bool
    {
        $attributes = $user->getAttributes();

        if (array_key_exists('active', $attributes) && $attributes['active'] !== null) {
            return (bool) $attributes['active'];
        }

        if (array_key_exists('status', $attributes) && $attributes['status'] !== null) {
            return $attributes['status'] === 'active';
        }

        return true;
    }
}
