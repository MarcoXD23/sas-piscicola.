<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraNocturna extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'bitacoras_nocturnas';

    public const ESTADO_NORMAL = 'normal';

    public const ESTADO_ANOMALIA = 'anomalia';

    public const ESTADO_FUGA_MONJE = 'fuga_monje';

    public const ESTADO_DEPREDADOR = 'depredador';

    public const MONJE_OPTIMO = 'optimo';

    public const MONJE_BAJO = 'bajo';

    public const MONJE_REBOSE = 'rebose';

    public const MONJE_FUGA = 'fuga';

    public const MALLA_BUENO = 'bueno';

    public const MALLA_DANADA = 'danada';

    public const MALLA_AJUSTADA = 'ajustada';

    protected $fillable = [
        'finca_id',
        'user_id',
        'fecha',
        'hora_ronda',
        'estanque_id',
        'estado',
        'nivel_agua_monje',
        'estado_mallas',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estanque(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    /**
     * Scope para consultar rondas con anomalías detectadas.
     */
    public function scopeAnomalias($query)
    {
        return $query->where('estado', '!=', self::ESTADO_NORMAL);
    }
}
