<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use BelongsToFinca, HasFactory;

    public const TYPE_PESCA = 'pesca_cosecha';

    public const TYPE_ALEVINOS = 'llegada_alevinos';

    public const TYPE_ALIMENTO = 'llegada_alimento';

    public const TYPE_VISITA = 'visita_general';

    public const STATUS_PROGRAMADO = 'programado';

    public const STATUS_EN_PROGRESO = 'en_progreso';

    public const STATUS_COMPLETADO = 'completado';

    public const STATUS_CANCELADO = 'cancelado';

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'estimated_kg' => 'decimal:2',
            'feed_weight_kg' => 'decimal:2',
            'fingerlings_quantity' => 'integer',
            'feed_bags_count' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    protected $appends = [
        'formatted_event_time',
    ];

    /**
     * Retorna la hora del evento en formato estricto de 12 horas con indicador AM/PM.
     */
    public function getFormattedEventTimeAttribute(): ?string
    {
        if (! $this->event_time) {
            return null;
        }

        try {
            return Carbon::parse($this->event_time, 'America/Bogota')->format('g:i A');
        } catch (\Throwable) {
            return $this->event_time;
        }
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by_user_id');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->orderBy('event_time', 'asc');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('event_date', $date);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('event_date', $year)
            ->whereMonth('event_date', $month);
    }
}
