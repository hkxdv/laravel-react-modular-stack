<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\AuthenticatableUser;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Traits\CrossGuardPermissions;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Modelo de Usuario para el personal interno (Staff).
 *
 * @mixin \Spatie\Permission\Traits\HasRoles
 * @mixin \App\Traits\CrossGuardPermissions
 *
 * @use HasFactory<\Database\Factories\StaffUsersFactory>
 */
final class StaffUsers extends Authenticatable implements AuthenticatableUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\StaffUsersFactory> */
    use CrossGuardPermissions, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    protected $guard_name = 'staff';

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
    ];

    /**
     * Atributos agregados al array/JSON automáticamente.
     * Esto permite exponer 'avatar' como un atributo computado.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'avatar',
    ];

    /**
     * Verifica si el usuario está activo.
     */
    public function isActive(): bool
    {
        // Por defecto, todos los usuarios staff están activos
        // Se puede extender para verificar campos como 'is_active' o 'status'
        return true;
    }

    /**
     * Verifica si el usuario ha sido eliminado (soft delete).
     */
    public function trashed(): bool
    {
        // Este modelo no usa soft deletes por defecto
        // Se puede extender si se implementa SoftDeletes trait
        return false;
    }

    /**
     * Configura las opciones para el registro de actividad.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(
                fn (string $eventName) => "El usuario ha sido {$this->getEventDescription($eventName)}"
            );
    }

    /**
     * Envía la notificación de restablecimiento de contraseña.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Envía la notificación de verificación de correo electrónico.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * Define la relación con la información de inicio de sesión del usuario.
     */
    public function loginInfos(): HasMany
    {
        return $this->hasMany(StaffUsersLoginInfo::class, 'staff_user_id');
    }

    /**
     * Obtiene el nombre de visualización del usuario.
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Obtiene el guard de autenticación para el usuario.
     */
    public function getAuthGuard(): string
    {
        return 'staff';
    }

    /**
     * Verifica si un intento de inicio de sesión es sospechoso (desde una IP/dispositivo no conocido).
     */
    public function isSuspiciousLogin(
        ?string $ipAddress,
        ?string $userAgent
    ): bool {
        if (! $ipAddress || ! $userAgent) {
            return true; // No hay datos suficientes, se considera sospechoso.
        }

        // Busca si ya tenemos registros de este dispositivo/IP o si es un dispositivo de confianza.
        $isKnown = $this->loginInfos()
            ->where(
                function ($query) use ($ipAddress, $userAgent) {
                    $query
                        ->where('ip_address', $ipAddress)
                        ->where('user_agent', $userAgent)
                        ->orWhere('is_trusted', true);
                }
            )
            ->exists();

        return ! $isKnown;
    }

    /**
     * Registra un nuevo inicio de sesión para el usuario.
     *
     * @param  array<string, mixed>  $deviceInfo
     */
    public function recordLogin(
        ?string $ipAddress,
        ?string $userAgent,
        array $deviceInfo = []
    ): StaffUsersLoginInfo {
        return $this->loginInfos()->create([
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device'] ?? null,
            'browser' => $deviceInfo['browser'] ?? null,
            'platform' => $deviceInfo['platform'] ?? null,
            'is_mobile' => $deviceInfo['is_mobile'] ?? false,
            'is_trusted' => false, // Por defecto, los nuevos dispositivos no son de confianza.
            'last_login_at' => now(),
            'login_count' => 1,
        ]);
    }

    /**
     * Atributo computado para obtener los permisos del usuario filtrados para el frontend.
     * Excluye permisos sensibles que no deberían ser expuestos en el lado del cliente.
     *
     * @return array<string>
     */
    public function getFrontendPermissionsAttribute(): array
    {
        // Obtiene todos los permisos únicos (directos y vía roles) a través del trait.
        $allPermissions = $this->getAllCrossGuardPermissions();

        // Define patrones de permisos a excluir del frontend.
        $excludePatterns = [
            'delete-',
            'manage-',
            'admin-',
        ];

        return array_filter(
            $allPermissions,
            function ($permission) use ($excludePatterns) {
                foreach ($excludePatterns as $pattern) {
                    if (str_starts_with($permission, $pattern)) {
                        return false; // Excluir si el permiso comienza con un patrón no deseado.
                    }
                }

                return true;
            }
        );
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return array_merge($this->toArray(), []);
    }

    /**
     * Accessor para obtener la URL del avatar del usuario.
     * (Se removió la dependencia a ContactStaffUser/contactProfile.)
     */
    public function getAvatarAttribute(): ?string
    {
        return null;
    }

    /**
     * Devuelve una descripción legible del evento.
     */
    protected function getEventDescription(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'creado',
            'updated' => 'actualizado',
            'deleted' => 'eliminado',
            default => $eventName,
        };
    }

    /**
     * Obtiene los atributos que deberían ser casteados.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
