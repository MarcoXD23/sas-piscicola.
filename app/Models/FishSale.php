<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishSale extends Model
{
    use BelongsToFinca;

    public const TYPE_VISITOR = 'visitante';

    public const TYPE_WORKER = 'trabajador';

    public const DEFAULT_PRICE_VISITOR = 9000.00;

    public const DEFAULT_PRICE_WORKER = 7000.00;

    protected $fillable = [
        'finca_id',
        'sale_date',
        'customer_type',
        'customer_name',
        'kilos_sold',
        'price_per_kg',
        'cash_received',
        'total_amount',
        'payment_method',
        'registered_by_user_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date:Y-m-d',
            'kilos_sold' => 'decimal:2',
            'price_per_kg' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (FishSale $sale) {
            if ($sale->customer_type === self::TYPE_VISITOR) {
                $unitPrice = $sale->price_per_kg ?: self::DEFAULT_PRICE_VISITOR;
                $sale->price_per_kg = $unitPrice;

                // Si se proporcionó dinero en efectivo recaudado y no los kilos
                if ($sale->cash_received > 0 && (! $sale->kilos_sold || $sale->kilos_sold <= 0)) {
                    $sale->kilos_sold = round($sale->cash_received / $unitPrice, 2);
                    $sale->total_amount = $sale->cash_received;
                } elseif ($sale->kilos_sold > 0) {
                    $sale->total_amount = round($sale->kilos_sold * $unitPrice, 2);
                    if (! $sale->cash_received) {
                        $sale->cash_received = $sale->total_amount;
                    }
                }
            } elseif ($sale->customer_type === self::TYPE_WORKER) {
                $unitPrice = $sale->price_per_kg ?: self::DEFAULT_PRICE_WORKER;
                $sale->price_per_kg = $unitPrice;
                if ($sale->kilos_sold > 0) {
                    $sale->total_amount = round($sale->kilos_sold * $unitPrice, 2);
                }
            }
        });
    }

    /**
     * Convierte dinero en efectivo a kilos vendidos para visitantes a $9.000 COP/kg.
     */
    public static function calculateKilosFromCash(float $cash, float $pricePerKg = self::DEFAULT_PRICE_VISITOR): float
    {
        if ($pricePerKg <= 0) {
            return 0.0;
        }

        return round($cash / $pricePerKg, 2);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    /**
     * Retorna el precio sugerido por defecto según el tipo de cliente.
     */
    public static function defaultPriceFor(string $customerType): float
    {
        return $customerType === self::TYPE_WORKER
            ? self::DEFAULT_PRICE_WORKER
            : self::DEFAULT_PRICE_VISITOR;
    }
}
