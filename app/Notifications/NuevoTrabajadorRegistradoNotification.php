<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevoTrabajadorRegistradoNotification extends Notification
{
    use Queueable;

    public function __construct(public User $worker) {}

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
        $fincaNombre = $this->worker->finca?->nombre ?? 'Finca Piscícola Principal';
        $fechaRegistro = $this->worker->created_at
            ? $this->worker->created_at->timezone('America/Bogota')->format('d/m/Y h:i A')
            : now()->timezone('America/Bogota')->format('d/m/Y h:i A');

        return (new MailMessage)
            ->subject('[El SAS Piscícola] Solicitud de Registro de Nuevo Trabajador - '.$this->worker->name)
            ->greeting('Hola, '.($notifiable->name ?? 'Administrador').':')
            ->line('Se ha registrado un nuevo aspirante o trabajador en la plataforma y se encuentra a la espera de aprobación y asignación de rol operativo.')
            ->line('Detalles del registro:')
            ->line('• Nombre Completo: '.$this->worker->name)
            ->line('• Cédula de Ciudadanía: '.($this->worker->document_number ?? 'No especificada'))
            ->line('• Correo Electrónico: '.$this->worker->email)
            ->line('• Finca Destino: '.$fincaNombre)
            ->line('• Fecha y Hora: '.$fechaRegistro)
            ->action('Revisar y Asignar Rol', url('/admin/personal'))
            ->line('Por favor ingresa al módulo de Personal para definir su rol operativo (Trabajador de Campo, Celador Nocturno, etc.) y su tipo de contrato (Fijo, Destajo Semanal, Temporal) para habilitar su acceso.')
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
            'worker_name' => $this->worker->name,
            'document_number' => $this->worker->document_number,
            'email' => $this->worker->email,
            'finca_id' => $this->worker->finca_id,
            'finca_nombre' => $this->worker->finca?->nombre ?? 'Finca Piscícola Principal',
            'mensaje' => 'Nuevo trabajador registrado pendiente de asignación de rol.',
        ];
    }
}
