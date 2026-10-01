<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CalendarEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CalendarEvent $calendarEvent,
        public string $action = 'creado' // 'creado' o 'actualizado'
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
        $event = $this->calendarEvent;
        $details = $this->buildDetailsSummary($event);
        $actionText = $this->action === 'actualizado' ? 'modificado' : 'programado';

        $formattedTime = $event->formatted_event_time ?? ($event->event_time ? Carbon::parse($event->event_time, 'America/Bogota')->format('g:i A') : null);
        $timeStr = $formattedTime ? " a las {$formattedTime}" : '';
        $titleAlert = "Alerta de Agenda: Evento {$this->action}";
        $message = "El Jefe de Finca ha {$actionText} el evento '{$event->title}' ({$this->getEventTypeName($event->event_type)}) para el {$event->event_date?->format('Y-m-d')}{$timeStr}. {$details}";

        return [
            'type' => 'calendar_event_alert',
            'action' => $this->action,
            'event_id' => $event->id,
            'finca_id' => $event->finca_id,
            'title' => $titleAlert,
            'event_title' => $event->title,
            'event_type' => $event->event_type,
            'event_date' => $event->event_date?->format('Y-m-d'),
            'event_time' => $formattedTime ?? $event->event_time,
            'event_time_12h' => $formattedTime,
            'status' => $event->status,
            'details' => $details,
            'scheduled_by' => $event->createdBy?->name,
            'message' => $message,
        ];
    }

    private function buildDetailsSummary(CalendarEvent $event): string
    {
        return match ($event->event_type) {
            CalendarEvent::TYPE_PESCA => 'Estanque: '.($event->pond?->name ?? "ID {$event->pond_id}").", Cantidad estimada: {$event->estimated_kg} kg.",
            CalendarEvent::TYPE_ALEVINOS => "Cantidad: {$event->fingerlings_quantity} alevinos, Etapa: {$event->stage}.",
            CalendarEvent::TYPE_ALIMENTO => "Concentrado: {$event->feed_type}, Bultos: {$event->feed_bags_count}, Total: {$event->feed_weight_kg} kg.",
            CalendarEvent::TYPE_VISITA => "Notas de inspección: {$event->inspection_notes}",
            default => $event->notes ?? 'Sin observaciones adicionales.',
        };
    }

    private function getEventTypeName(string $type): string
    {
        return match ($type) {
            CalendarEvent::TYPE_PESCA => 'Pesca / Cosecha',
            CalendarEvent::TYPE_ALEVINOS => 'Llegada de Alevinos',
            CalendarEvent::TYPE_ALIMENTO => 'Llegada de Alimento',
            CalendarEvent::TYPE_VISITA => 'Visita o Asunto General',
            default => 'Evento de Agenda',
        };
    }
}
