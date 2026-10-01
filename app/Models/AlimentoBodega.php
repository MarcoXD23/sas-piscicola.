<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlimentoBodega extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'alimentos_bodega';

    protected $fillable = [
        'finca_id',
        'nombre_concentrado',
        'proteina_porcentaje',
        'peso_bulto_kg',
        'stock_bultos',
        'stock_kilos_actual',
        'umbral_alerta_bultos',
        'costo_unitario_bulto',
    ];

    protected function casts(): array
    {
        return [
            'proteina_porcentaje' => 'integer',
            'peso_bulto_kg' => 'decimal:2',
            'stock_bultos' => 'decimal:2',
            'stock_kilos_actual' => 'decimal:2',
            'umbral_alerta_bultos' => 'decimal:2',
            'costo_unitario_bulto' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AlimentoBodega $alimento) {
            $pesoBulto = (float) ($alimento->peso_bulto_kg ?: 40.00);

            if ($alimento->isDirty('stock_kilos_actual') && ! $alimento->isDirty('stock_bultos')) {
                $alimento->stock_bultos = round((float) $alimento->stock_kilos_actual / $pesoBulto, 2);
            } elseif ($alimento->isDirty('stock_bultos') && ! $alimento->isDirty('stock_kilos_actual')) {
                $alimento->stock_kilos_actual = round((float) $alimento->stock_bultos * $pesoBulto, 2);
            } elseif (! $alimento->isDirty('stock_kilos_actual') && ! $alimento->isDirty('stock_bultos')) {
                if ((float) $alimento->stock_kilos_actual <= 0 && (float) $alimento->stock_bultos > 0) {
                    $alimento->stock_kilos_actual = round((float) $alimento->stock_bultos * $pesoBulto, 2);
                }
            }
        });
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBodega::class, 'alimento_id');
    }

    /**
     * Consumo promedio diario de este alimento en los últimos N días.
     */
    public function consumoDiarioPromedio(int $dias = 14): float
    {
        $startDate = now()->subDays($dias)->toDateString();

        $totalConsumoKilos = (float) $this->movimientos()
            ->where('tipo_movimiento', MovimientoBodega::TIPO_SALIDA_ALIMENTACION)
            ->where('fecha', '>=', $startDate)
            ->sum('cantidad_kilos');

        if ($totalConsumoKilos <= 0) {
            return 0.0;
        }

        return round($totalConsumoKilos / max(1, $dias), 2);
    }

    /**
     * Días de autonomía restante según consumo diario promedio.
     */
    public function diasAutonomia(int $dias = 14): float
    {
        $consumoDiario = $this->consumoDiarioPromedio($dias);

        if ($consumoDiario <= 0) {
            return 999.0;
        }

        return round((float) $this->stock_kilos_actual / $consumoDiario, 1);
    }

    /**
     * Determina si el stock está en o por debajo del umbral mínimo de alerta.
     */
    public function isBajoStock(): bool
    {
        return (float) $this->stock_bultos <= (float) $this->umbral_alerta_bultos;
    }
}
