<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use BelongsToFinca;

    public const SHIFT_WEEKEND = 'fin_de_semana';

    public const SHIFT_HOLIDAY = 'festivo';

    public const SHIFT_FEEDING_BLOCK = 'bloque_alimentacion';

    public const SHIFT_REGULAR = 'lunes_a_viernes';

    public const SHIFT_NIGHT = 'nocturno';

    protected $fillable = [
        'finca_id',
        'user_id',
        'schedule_date',
        'shift_type',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schedule_date' => 'date:Y-m-d',
        ];
    }

    protected $appends = [
        'formatted_start_time',
        'formatted_end_time',
    ];

    /**
     * Hora de inicio en formato estricto de 12 horas AM/PM (America/Bogota).
     */
    public function getFormattedStartTimeAttribute(): ?string
    {
        if (! $this->start_time) {
            return null;
        }

        try {
            return Carbon::parse($this->start_time, 'America/Bogota')->format('g:i A');
        } catch (\Throwable) {
            return $this->start_time;
        }
    }

    /**
     * Hora de fin en formato estricto de 12 horas AM/PM (America/Bogota).
     */
    public function getFormattedEndTimeAttribute(): ?string
    {
        if (! $this->end_time) {
            return null;
        }

        try {
            return Carbon::parse($this->end_time, 'America/Bogota')->format('g:i A');
        } catch (\Throwable) {
            return $this->end_time;
        }
    }

    /**
     * Trabajador asignado al turno.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bitácoras de alimentación realizadas durante este turno.
     */
    public function feedingLogs(): HasMany
    {
        return $this->hasMany(FeedingLog::class);
    }

    /**
     * Scope para filtrar por fecha específica.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('schedule_date', $date);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar por trabajador.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
