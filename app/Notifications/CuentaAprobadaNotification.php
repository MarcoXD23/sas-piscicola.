<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CuentaAprobadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public User $worker,
        public string $roleLabel,
        public string $contractLabel
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[El SAS Piscícola] Tu cuenta ha sido activada')
            ->greeting('Hola, '.$this->worker->name.':')
            ->line('La administración de la finca ha revisado y aprobado tu registro en la plataforma El SAS Piscícola.')
            ->line('Detalles de tu asignación:')
            ->line('• Rol Asignado: '.$this->roleLabel)
            ->line('• Modalidad de Contrato: '.$this->contractLabel)
            ->action('Iniciar Sesión Ahora', route('login'))
            ->line('Ya puedes acceder al sistema con tu correo electrónico o tu número de cédula y la contraseña que registraste.')
            ->salutation('Atentamente, Sistema de Gestión El SAS Piscícola');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'worker_id' => $this->worker->id,
            'role' => $this->worker->role,
            'role_label' => $this->roleLabel,
            'contract_label' => $this->contractLabel,
            'mensaje' => 'Tu cuenta ha sido aprobada y activada con éxito.',
        ];
    }
}
