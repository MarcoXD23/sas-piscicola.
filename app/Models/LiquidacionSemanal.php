<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionSemanal extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'liquidaciones_semanales';

    protected $fillable = [
        'finca_id',
        'personal_temporal_id',
        'user_id',
        'corte_sabado',
        'tipo_pago',
        'unidades_trabajadas',
        'tarifa',
        'total_bruto',
        'deducciones',
        'total_neto',
        'estado',
        'liquidado_por_user_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'corte_sabado' => 'date',
            'unidades_trabajadas' => 'decimal:2',
            'tarifa' => 'decimal:2',
            'total_bruto' => 'decimal:2',
            'deducciones' => 'decimal:2',
            'total_neto' => 'decimal:2',
        ];
    }

    public function personalTemporal(): BelongsTo
    {
        return $this->belongsTo(PersonalTemporal::class, 'personal_temporal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function liquidadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liquidado_por_user_id');
    }
}
