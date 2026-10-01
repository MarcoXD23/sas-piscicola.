<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishCredit extends Model
{
    use BelongsToFinca;
    use HasFactory;

    public const DEFAULT_PRICE_PER_KG = 7000.00;

    public const STATUS_PENDIENTE = 'pendiente';

    public const STATUS_DESCONTADO_SABADO = 'descontado_sabado';

    public const STATUS_ACUMULADO_MENSUAL = 'acumulado_mensual';

    public const STATUS_PAGADO = 'pagado';

    protected $fillable = [
        'finca_id',
        'user_id',
        'credit_date',
        'kilos',
        'price_per_kg',
        'total_amount',
        'status',
        'payroll_settlement_id',
        'registered_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_date' => 'date',
            'kilos' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FishCredit $credit) {
            if (! $credit->price_per_kg || $credit->price_per_kg <= 0) {
                $credit->price_per_kg = self::DEFAULT_PRICE_PER_KG;
            }
            $credit->total_amount = round($credit->kilos * $credit->price_per_kg, 2);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollSettlement(): BelongsTo
    {
        return $this->belongsTo(PayrollSettlement::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
