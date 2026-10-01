<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngresoAlimento extends Model
{
    use HasFactory;

    protected $table = 'ingresos_alimento';

    /**
     * Campos estrictamente logísticos y físicos de bodega (Sin precios ni costos monetarios).
     */
    protected $fillable = [
        'fecha_recepcion',
        'proveedor',
        'tipo_concentrado',
        'bultos_recibidos',
        'peso_bulto_kg',
        'kilos_totales',
        'lote_fabrica',
        'recibido_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_recepcion' => 'date',
            'bultos_recibidos' => 'decimal:2',
            'peso_bulto_kg' => 'decimal:2',
            'kilos_totales' => 'decimal:2',
        ];
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }
}
