<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Eloquent\Models;

use App\Interfaces\AuthenticatableUser;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Infrastructure\Laravel\Traits\CanBeImpersonated;
use Modules\Core\Infrastructure\Laravel\Traits\HasCrossGuardPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

use function Foundry\Helpers\cacheInt;
use function Foundry\Helpers\userId;

/**
 * Modelo base abstracto para usuarios de dominio.
 *
 * Comportamiento compartido: traits de Eloquent, acceso a avatar,
 * permisos frontend con caché y valores por defecto de isActive/trashed.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property list<string> $fillable
 * @property string $table
 */
abstract class AbstractDomainUser extends Authenticatable implements AuthenticatableUser
{
    use CanBeImpersonated;
    use HasApiTokens;
    use HasCrossGuardPermissions;
    use HasRoles;
    use LogsActivity;
    use Notifiable;

    /**
     * Atributos agregados al array/JSON automáticamente.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * Obtiene el "guard" de autenticación asociado con este tipo de usuario.
     */
    abstract public function getAuthGuard(): string;

    /**
     * Obtiene el nombre completo o de visualización del usuario.
     */
    abstract public function getDisplayName(): string;

    /**
     * Verifica si el usuario está activo.
     */
    final public function isActive(): bool
    {
        return true;
    }

    /**
     * Verifica si el usuario ha sido eliminado (soft delete).
     */
    final public function trashed(): bool
    {
        return false;
    }

    /**
     * Obtiene el avatar del usuario.
     */
    protected function getAvatarAttribute(): string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Obtiene los permisos frontend del usuario.
     *
     * @return list<string>
     */
    protected function getFrontendPermissionsAttribute(): array
    {
        $userId = userId($this);
        $version = cacheInt('user.'.$userId.'.perm_version', 0);
        $cacheKey = 'user.'.$userId.'.v'.$version.'.frontend_permissions';

        $result = Cache::remember(
            $cacheKey,
            now()->addMinutes(10),
            fn (): array => $this->getAllCrossGuardPermissions()
        );

        return array_values(array_filter($result, is_string(...)));
    }
}
