<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioAlimento extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'inventario_alimento';

    protected $fillable = [
        'finca_id',
        'tipo_concentrado',
        'proteina_porcentaje',
        'stock_actual_kg',
        'stock_minimo_alerta_kg',
        'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'proteina_porcentaje' => 'decimal:2',
            'stock_actual_kg' => 'decimal:2',
            'stock_minimo_alerta_kg' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Calcula los días restantes de alimento en bodega basado en el consumo diario proyectado de la finca.
     */
    public function calcularDiasRestantes(float $consumoDiarioKg): float
    {
        if ($consumoDiarioKg <= 0) {
            return 999.0;
        }

        return round(((float) $this->stock_actual_kg) / $consumoDiarioKg, 1);
    }

    /**
     * Determina si el alimento se encuentra en nivel crítico (por stock mínimo o días de consumo).
     */
    public function tieneAlertaCritica(float $consumoDiarioKg, int $diasUmbral = 5): bool
    {
        $diasRestantes = $this->calcularDiasRestantes($consumoDiarioKg);

        return ((float) $this->stock_actual_kg) <= ((float) $this->stock_minimo_alerta_kg) || $diasRestantes <= $diasUmbral;
    }
}
