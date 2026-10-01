<?php

namespace App\Models;

/**
 * Modelo EventoAgenda (Alias de CalendarEvent).
 * Representa las actividades y eventos programados en la agenda de la finca.
 */
class EventoAgenda extends CalendarEvent
{
    protected $table = 'calendar_events';

    protected $fillable = [
        'finca_id',
        'title',
        'event_type',
        'event_date',
        'event_time',
        'status',
        'pond_id',
        'estimated_kg',
        'fingerlings_quantity',
        'stage',
        'feed_type',
        'feed_bags_count',
        'feed_weight_kg',
        'inspection_notes',
        'notes',
        'created_by_user_id',
        'last_modified_by_user_id',
        'client_uuid',
        'synced_at',
    ];

    public function getTituloAttribute(): ?string
    {
        return $this->title;
    }

    public function setTituloAttribute(?string $value): void
    {
        $this->attributes['title'] = $value;
    }

    public function getTipoEventoAttribute(): ?string
    {
        return $this->event_type;
    }

    public function setTipoEventoAttribute(?string $value): void
    {
        $this->attributes['event_type'] = $value;
    }

    public function getFechaProgramadaAttribute(): ?string
    {
        return $this->event_date?->format('Y-m-d');
    }

    public function setFechaProgramadaAttribute($value): void
    {
        $this->attributes['event_date'] = $value;
    }

    public function getHoraProgramadaAttribute(): ?string
    {
        return $this->event_time;
    }

    public function setHoraProgramadaAttribute($value): void
    {
        $this->attributes['event_time'] = $value;
    }

    public function getLagoIdAttribute(): ?int
    {
        return $this->pond_id;
    }

    public function setLagoIdAttribute($value): void
    {
        $this->attributes['pond_id'] = $value;
    }

    public function getKilosEstimadosAttribute(): ?float
    {
        return $this->estimated_kg ? (float) $this->estimated_kg : null;
    }

    public function setKilosEstimadosAttribute($value): void
    {
        $this->attributes['estimated_kg'] = $value;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->created_by_user_id;
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['created_by_user_id'] = $value;
    }
}
