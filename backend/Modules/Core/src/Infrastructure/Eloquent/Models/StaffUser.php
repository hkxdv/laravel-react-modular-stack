<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Eloquent\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Database\Factories\StaffUsersFactory;
use Spatie\Activitylog\LogOptions;

/**
 * Modelo de Usuario para el personal interno (Staff).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StaffUsersLoginInfo> $loginInfos
 *
 * @use HasFactory<StaffUsersFactory>
 */
class StaffUser extends AbstractDomainUser implements MustVerifyEmail
{
    /** @use HasFactory<StaffUsersFactory> */
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'staff_users';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * Los atributos que deberían estar ocultos para las serializaciones.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'last_activity' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    // @phpstan-ignore property.onlyWritten (used magically by Spatie HasRoles trait)
    private string $guard_name = 'staff';

    /**
     * {@inheritDoc}
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthGuard(): string
    {
        return 'staff';
    }

    /**
     * Relación con el historial de inicios de sesión.
     *
     * @return HasMany<StaffUsersLoginInfo, $this>
     */
    public function loginInfos(): HasMany
    {
        return $this->hasMany(StaffUsersLoginInfo::class, 'staff_user_id');
    }

    /**
     * Registra un nuevo inicio de sesión.
     *
     * @param  array<string, mixed>  $deviceInfo
     */
    public function recordLogin(
        ?string $ip,
        ?string $userAgent,
        array $deviceInfo
    ): StaffUsersLoginInfo {
        // Buscar si ya existe un registro para este dispositivo e IP
        $query = $this->loginInfos()
            ->where('ip_address', $ip);
        if ($userAgent !== null) {
            $query->whereNotNull('user_agent');
        }

        $loginInfo = $query->get()
            ->filter(fn (StaffUsersLoginInfo $info): bool => $info->matches(
                $ip,
                $userAgent
            ))
            ->first();

        if ($loginInfo) {
            $loginInfo->increment('login_count');
            $loginInfo->update(['last_login_at' => now()]);

            return $loginInfo;
        }

        /** @var StaffUsersLoginInfo */
        return $this->loginInfos()->create([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'platform' => $deviceInfo['platform'] ?? null,
            'is_mobile' => $deviceInfo['is_mobile'] ?? false,
            'is_trusted' => false, // Por defecto no es de confianza hasta que se verifique
            'last_login_at' => now(),
            'login_count' => 1,
        ]);
    }

    /**
     * Verifica si el inicio de sesión es sospechoso.
     */
    public function isSuspiciousLogin(?string $ip, ?string $userAgent): bool
    {
        // Si es el primer login, no es sospechoso
        if ($this->loginInfos()->count() <= 1) {
            return false;
        }

        // Buscar si existe un dispositivo de confianza que coincida
        $knownDevice = $this->loginInfos()
            ->where('is_trusted', true)
            ->get()
            ->filter(fn (StaffUsersLoginInfo $info): bool => $info->matches(
                $ip,
                $userAgent
            ))
            ->first();

        return ! $knownDevice;
    }

    /**
     * Opciones para el registro de actividad.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<StaffUser>
     */
    protected static function newFactory()
    {
        return StaffUsersFactory::new();
    }
}
