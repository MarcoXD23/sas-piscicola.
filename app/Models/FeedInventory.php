<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedInventory extends Model
{
    use BelongsToFinca;

    protected $fillable = [
        'finca_id',
        'name',
        'category',
        'brand',
        'feed_type',
        'protein_percentage',
        'bag_weight_kg',
        'quantity_kg',
        'min_stock_alert_kg',
    ];

    protected function casts(): array
    {
        return [
            'protein_percentage' => 'decimal:2',
            'bag_weight_kg' => 'decimal:2',
            'quantity_kg' => 'decimal:2',
            'min_stock_alert_kg' => 'decimal:2',
        ];
    }

    /**
     * Consumo promedio diario de este alimento en los últimos N días.
     */
    public function dailyAverageConsumption(int $days = 14): float
    {
        $startDate = now()->subDays($days)->toDateString();

        $totalConsumption = (float) $this->feedingLogs()
            ->where('feeding_date', '>=', $startDate)
            ->sum('amount_kg');

        if ($totalConsumption <= 0) {
            // Si no hay bitácoras específicas asociadas a este inventario, buscar por nombre
            $totalConsumption = (float) FeedingLog::query()
                ->where('feed_inventory_id', $this->id)
                ->orWhere('feed_name', $this->name)
                ->where('feeding_date', '>=', $startDate)
                ->sum('amount_kg');
        }

        if ($totalConsumption <= 0) {
            return 0.0;
        }

        return round($totalConsumption / max(1, $days), 2);
    }

    /**
     * Cálculo automático de días de alimento restante según el consumo diario promedio.
     */
    public function daysOfFeedRemaining(int $days = 14): float
    {
        $dailyAvg = $this->dailyAverageConsumption($days);

        if ($dailyAvg <= 0) {
            return 999.0; // Consumo no registrado aún
        }

        return round($this->quantity_kg / $dailyAvg, 1);
    }

    /**
     * Indica si el inventario actual está por debajo del umbral mínimo de alerta.
     */
    public function isLowStock(): bool
    {
        $threshold = $this->min_stock_alert_kg ?? 100.0;

        return (float) $this->quantity_kg <= (float) $threshold;
    }

    /**
     * Disminuye la cantidad en bodega tras alimentar estanques.
     */
    public function subtractQuantity(float $amount): void
    {
        if ($this->quantity_kg >= $amount) {
            $this->decrement('quantity_kg', $amount);
        } else {
            throw new Exception("Inventario insuficiente de {$this->name}. Disponible: {$this->quantity_kg}kg, Requerido: {$amount}kg.");
        }
    }

    /**
     * Incrementa la cantidad en bodega tras recibir despacho/compra.
     */
    public function addQuantity(float $amount): void
    {
        $this->increment('quantity_kg', $amount);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class);
    }

    public function feedingLogs(): HasMany
    {
        return $this->hasMany(FeedingLog::class);
    }
}
