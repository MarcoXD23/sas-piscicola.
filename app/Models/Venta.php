<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'finca_id',
        'lote_id',
        'estanque_id',
        'harvest_order_id',
        'cliente',
        'kg_vendidos',
        'precio_por_kg',
        'total_venta',
        'forma_pago',
        'fecha',
        'caja_id',
        'user_id',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'kg_vendidos' => 'decimal:2',
            'precio_por_kg' => 'decimal:2',
            'total_venta' => 'decimal:2',
        ];
    }

    public function estanque(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    public function harvestOrder(): BelongsTo
    {
        return $this->belongsTo(HarvestOrder::class, 'harvest_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
