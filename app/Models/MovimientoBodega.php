<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoBodega extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'movimientos_bodega';

    public const TIPO_ENTRADA_COMPRA = 'entrada_compra';

    public const TIPO_SALIDA_ALIMENTACION = 'salida_alimentacion';

    public const TIPO_AJUSTE_MERMA = 'ajuste_merma';

    protected $fillable = [
        'finca_id',
        'alimento_id',
        'user_id',
        'tipo_movimiento',
        'cantidad_bultos',
        'cantidad_kilos',
        'fecha',
        'proveedor',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_bultos' => 'decimal:2',
            'cantidad_kilos' => 'decimal:2',
        ];
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(AlimentoBodega::class, 'alimento_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
