<?php

namespace App\Notifications;

use App\Models\Pond;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AlertaBoqueoCriticaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Pond $pond,
        public User $celador,
        public ?string $observaciones = null,
        public ?float $oxigenoMgL = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $hora12h = now()->timezone('America/Bogota')->format('g:i A');
        $oxigenoStr = $this->oxigenoMgL ? " (Oxígeno medido: {$this->oxigenoMgL} mg/L)" : '';

        return [
            'type' => 'alerta_boqueo_critica',
            'priority' => 'urgente',
            'finca_id' => $this->pond->finca_id,
            'pond_id' => $this->pond->id,
            'pond_name' => $this->pond->name,
            'pond_code' => $this->pond->code,
            'celador_id' => $this->celador->id,
            'celador_name' => $this->celador->name,
            'hora_reporte' => $hora12h,
            'title' => 'ALERTA CRÍTICA: Boqueo / Asfixia de Peces en '.$this->pond->name,
            'message' => "¡EMERGENCIA NOCTURNA! El celador {$this->celador->name} activó el BOTÓN DE PÁNICO por boqueo masivo en {$this->pond->name}{$oxigenoStr} a las {$hora12h}. Requiere encendido urgente de aireadores de emergencia y motobomba de recambio.",
            'observaciones' => $this->observaciones,
        ];
    }
}
