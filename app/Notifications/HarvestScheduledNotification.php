<?php

namespace App\Notifications;

use App\Models\HarvestOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HarvestScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public HarvestOrder $harvestOrder
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
        return [
            'type' => 'harvest_scheduled',
            'harvest_order_id' => $this->harvestOrder->id,
            'pond_id' => $this->harvestOrder->pond_id,
            'pond_name' => $this->harvestOrder->pond?->name,
            'scheduled_date' => $this->harvestOrder->scheduled_date?->format('Y-m-d'),
            'estimated_kg' => (float) $this->harvestOrder->estimated_kg,
            'scheduled_by' => $this->harvestOrder->scheduledBy?->name,
            'title' => 'Aviso de Cosecha Programada',
            'message' => "El Jefe de Finca ha programado la pesca del {$this->harvestOrder->pond?->name} para el {$this->harvestOrder->scheduled_date?->format('Y-m-d')}. Estimado: {$this->harvestOrder->estimated_kg} kg.",
        ];
    }
}
