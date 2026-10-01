<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLabor extends Model
{
    use BelongsToFinca;

    public const TYPE_FIJO = 'fijo';

    public const TYPE_TEMPORAL = 'temporal';

    public const LABOR_RAYADORES = 'rayadores';

    public const LABOR_LAVADO = 'lavado_estanques';

    public const LABOR_PESCA = 'pesca';

    public const LABOR_EMPAQUE = 'empaque';

    public const LABOR_MANTENIMIENTO = 'mantenimiento';

    public const LABOR_OTRO = 'otro';

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_LIQUIDADO = 'liquidado';

    protected $fillable = [
        'finca_id',
        'payroll_settlement_id',
        'worker_name',
        'worker_id_card',
        'user_id',
        'employment_type',
        'pond_id',
        'work_date',
        'labor_type',
        'daily_wage',
        'hours_worked',
        'payment_status',
        'registered_by_user_id',
        'observations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
            'daily_wage' => 'decimal:2',
            'hours_worked' => 'decimal:2',
        ];
    }

    public function payrollSettlement(): BelongsTo
    {
        return $this->belongsTo(PayrollSettlement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', self::STATUS_PENDIENTE);
    }

    public function scopeTemporal(Builder $query): Builder
    {
        return $query->where('employment_type', self::TYPE_TEMPORAL);
    }

    public function scopeFijo(Builder $query): Builder
    {
        return $query->where('employment_type', self::TYPE_FIJO);
    }

    public function scopeForWeek(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('work_date', [$startDate, $endDate]);
    }

    public function isFijo(): bool
    {
        if ($this->employment_type === self::TYPE_FIJO) {
            return true;
        }

        return (bool) ($this->user && $this->user->isFijo());
    }

    public function isTemporal(): bool
    {
        return ! $this->isFijo();
    }
}
