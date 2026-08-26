<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Fakes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Infrastructure\Eloquent\Models\AbstractDomainUser;
use Modules\Core\Infrastructure\Laravel\Traits\CanBeImpersonated;
use Modules\Core\Infrastructure\Laravel\Traits\HasCrossGuardPermissions;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * Implementación concreta falsa de AbstractDomainUser para tests de Core.
 *
 * Permite validar la estructura y comportamiento del base sin depender de StaffUser (Admin).
 *
 * @use HasFactory<FakeDomainUserFactory>
 */
#[\Illuminate\Database\Eloquent\Attributes\Table(name: 'users')]
final class FakeDomainUser extends AbstractDomainUser
{
    use CanBeImpersonated;
    use HasApiTokens;
    use HasCrossGuardPermissions;

    /** @use HasFactory<FakeDomainUserFactory> */
    use HasFactory;

    use HasRoles;
    use LogsActivity;
    use Notifiable;

    public function getAuthGuard(): string
    {
        return 'web';
    }

    public function getDisplayName(): string
    {
        return $this->name ?? 'Fake User';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
