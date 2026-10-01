<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestOrder extends Model
{
    use BelongsToFinca;

    public const STATUS_PROGRAMADA = 'programada';

    public const STATUS_PESAJE = 'pesaje_completado';

    public const STATUS_DESPACHADA = 'despachada';

    public const STATUS_CANCELADA = 'cancelada';

    protected $fillable = [
        'finca_id',
        'pond_id',
        'scheduled_by_user_id',
        'scheduled_date',
        'estimated_kg',
        'gross_weight_kg',
        'baskets_count',
        'basket_tare_kg',
        'total_tare_kg',
        'weighing_batches',
        'net_weight_kg',
        'weighed_by_user_id',
        'weighed_at',
        'clean_weight_kg',
        'driver_name',
        'driver_id_card',
        'driver_vehicle_plate',
        'destination',
        'buyer_name',
        'dispatched_by_user_id',
        'dispatched_at',
        'status',
        'observations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date:Y-m-d',
            'weighed_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'estimated_kg' => 'float',
            'gross_weight_kg' => 'float',
            'baskets_count' => 'integer',
            'basket_tare_kg' => 'float',
            'total_tare_kg' => 'float',
            'weighing_batches' => 'array',
            'net_weight_kg' => 'float',
            'clean_weight_kg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (HarvestOrder $order) {
            if ($order->isDirty('gross_weight_kg') || $order->isDirty('basket_tare_kg') || $order->net_weight_kg === null) {
                if ($order->gross_weight_kg !== null && $order->baskets_count !== null) {
                    $tare = $order->basket_tare_kg ?: 2.0;
                    $order->net_weight_kg = max(0.0, round((float) $order->gross_weight_kg - ((int) $order->baskets_count * (float) $tare), 2));
                }
            }
        });
    }

    /**
     * Calcula la tara total y el peso neto a partir del peso bruto y canastillas.
     * Fórmula: Peso Neto = Peso Bruto - (Canastillas * Peso_Tara)
     */
    public function calculateNetWeight(?float $grossWeight = null, ?int $baskets = null, ?float $tare = null): float
    {
        $gross = $grossWeight ?? (float) $this->gross_weight_kg;
        $count = $baskets ?? (int) $this->baskets_count;
        $tareKg = $tare ?? (float) ($this->basket_tare_kg ?: 2.0);

        return max(0.0, round($gross - ($count * $tareKg), 2));
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by_user_id');
    }

    public function weighedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'weighed_by_user_id');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by_user_id');
    }

    /**
     * Rendimiento de limpieza: porcentaje de carne limpia obtenida respecto al peso neto o bruto.
     */
    public function getCleaningYieldAttribute(): ?float
    {
        $base = $this->net_weight_kg ?: $this->gross_weight_kg;

        if ($base && $this->clean_weight_kg && $base > 0) {
            return round(($this->clean_weight_kg / $base) * 100, 2);
        }

        return null;
    }

    /**
     * Accesor para tandas de pesaje guardadas en JSON en el modelo.
     *
     * @return array<int, mixed>
     */
    public function getWeighingsAttribute(): array
    {
        return $this->weighing_batches ?? [];
    }
}
