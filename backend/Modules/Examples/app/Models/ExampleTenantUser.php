<?php

declare(strict_types=1);

namespace Modules\Examples\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Examples\Database\Factories\ExampleTenantUserFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Modelo de ejemplo para usuarios tenant (multi-tenant).
 *
 * Modelo esqueleto que demuestra la abstracción de AbstractDomainUser
 * funcionando con el guard 'tenant' y columnas polimórficas de sesión.
 */
#[Fillable([
    'name',
    'email',
    'password',
])]
#[Hidden([
    'password',
    'remember_token',
])]
final class ExampleTenantUser extends AbstractDomainUser
{
    /** @use HasFactory<\Modules\Examples\Database\Factories\ExampleTenantUserFactory> */
    use HasFactory;

    use LogsActivity;

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

    protected static function newFactory(): ExampleTenantUserFactory
    {
        return ExampleTenantUserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
