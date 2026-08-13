<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Laravel\Listeners;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Contracts\AuditTrailInterface;

use function Foundry\Helpers\userId;

/**
 * Listener para registrar eventos sensibles en el canal de auditoría.
 *
 * Se conecta a eventos de permisos/roles para dejar rastro cuando se realizan
 * cambios que impactan seguridad y acceso.
 */
final readonly class LogSensitiveActionListener
{
    /**
     * @param  AuditTrailInterface  $auditTrail  Servicio de auditoría de dominio.
     */
    public function __construct(
        private AuditTrailInterface $auditTrail
    ) {
        //
    }

    /**
     * Maneja eventos sensibles y registra en el canal de auditoría.
     *
     * @param  object  $event  Evento disparado por el framework (p.ej., Spatie Permission).
     */
    public function handle(object $event): void
    {
        $user = isset($event->user) && $event->user instanceof Authenticatable
            ? $event->user
            : null;
        $uid = userId($user);

        $this->auditTrail->record('sensitive_action', [
            'event_class' => $event::class,
            'user_id' => $uid,
        ]);
    }
}
