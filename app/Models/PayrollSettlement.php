<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSettlement extends Model
{
    use BelongsToFinca;

    public const STATUS_BORRADOR = 'borrador';

    public const STATUS_LIQUIDADA = 'liquidada';

    public const STATUS_PAGADA = 'pagada';

    protected $fillable = [
        'finca_id',
        'week_start_date',
        'cutoff_date',
        'settlement_date',
        'total_jornales_count',
        'total_workers_count',
        'total_gross_amount',
        'total_deductions_amount',
        'total_net_amount',
        'total_amount',
        'status',
        'settled_by_user_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start_date' => 'date:Y-m-d',
            'cutoff_date' => 'date:Y-m-d',
            'settlement_date' => 'date:Y-m-d',
            'total_jornales_count' => 'integer',
            'total_workers_count' => 'integer',
            'total_gross_amount' => 'decimal:2',
            'total_deductions_amount' => 'decimal:2',
            'total_net_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function dailyLabors(): HasMany
    {
        return $this->hasMany(DailyLabor::class);
    }

    public function settledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by_user_id');
    }
}
