<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalTemporal extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'personal_temporal';

    public const TIPO_DESTAJO = 'destajo';

    public const TIPO_JORNAL = 'jornal';

    protected $fillable = [
        'finca_id',
        'nombre',
        'documento',
        'telefono',
        'tipo_pago',
        'tarifa',
        'deducciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'tarifa' => 'decimal:2',
            'deducciones' => 'decimal:2',
        ];
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(LiquidacionSemanal::class, 'personal_temporal_id');
    }
}
