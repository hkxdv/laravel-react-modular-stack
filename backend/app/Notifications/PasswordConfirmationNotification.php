<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación de seguridad enviada cuando un usuario confirma una acción sensible con su contraseña.
 *
 * Esta notificación informa al usuario que su contraseña ha sido utilizada para autorizar
 * una acción importante, proporcionando detalles contextuales como la acción realizada,
 * la IP y el dispositivo, para que pueda verificar la legitimidad de la actividad.
 */
class PasswordConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Crea una nueva instancia de la notificación.
     *
     * @param  string  $actionType  Descripción de la acción que se confirmó (ej. "eliminar cuenta").
     * @param  string|null  $ipAddress  La dirección IP desde la que se realizó la confirmación.
     * @param  string|null  $userAgent  El agente de usuario (dispositivo) utilizado.
     */
    public function __construct(
        public string $actionType = 'acción sensible',
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {}

    /**
     * Obtiene los canales de entrega de la notificación.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construye la representación por correo electrónico de la notificación.
     *
     * @param  mixed  $notifiable  La entidad que recibe la notificación.
     * @return \Illuminate\Notifications\Messages\MailMessage El mensaje de correo electrónico configurado.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // --- Construcción del Mensaje Principal ---
        $message = (new MailMessage)
            ->subject('Alerta de Seguridad: Contraseña Confirmada para Acción Sensible')
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Te informamos que tu contraseña ha sido utilizada para confirmar la siguiente acción: **{$this->actionType}**.");

        // --- Detalles de la Confirmación ---
        $message->line('**Detalles de la confirmación:**')
            ->line('- **Fecha y hora:** ' . now()->format('d/m/Y H:i:s'));

        if ($this->ipAddress) {
            $message->line("- **Dirección IP:** {$this->ipAddress}");
        }

        if ($this->userAgent) {
            $message->line("- **Dispositivo:** {$this->userAgent}");
        }

        // --- Advertencia de Seguridad y Acciones ---
        $message->line('Si no fuiste tú quien realizó esta acción, tu cuenta podría estar comprometida. Te recomendamos cambiar tu contraseña inmediatamente.')
            ->action('Cambiar contraseña', route('password.request'))
            ->line('Este es un correo electrónico automático de seguridad. Por favor, no respondas a este mensaje.');

        return $message;
    }

    /**
     * Obtiene la representación de la notificación como un array.
     *
     * @param  mixed  $notifiable  La entidad que recibe la notificación.
     * @return array<string, mixed> Los datos de la notificación.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action_type' => $this->actionType,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'time' => now()->toIso8601String(),
        ];
    }
}
