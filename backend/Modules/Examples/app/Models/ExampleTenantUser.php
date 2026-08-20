<?php

declare(strict_types=1);

namespace Modules\Examples\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de ejemplo para usuarios tenant (multi-tenant).
 *
 * Modelo esqueleto que demuestra la abstracción de AbstractDomainUser
 * funcionando con el guard 'tenant' y columnas polimórficas de sesión.
 */
final class ExampleTenantUser extends AbstractDomainUser
{
    /** @use HasFactory<\Modules\Examples\Database\Factories\ExampleTenantUserFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getAuthGuard(): string
    {
        return 'tenant';
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected static function newFactory(): \Modules\Examples\Database\Factories\ExampleTenantUserFactory
    {
        return \Modules\Examples\Database\Factories\ExampleTenantUserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
