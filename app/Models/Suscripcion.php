<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';

    public const ESTADO_SUSPENDIDA = 'suspendida';

    public const ESTADO_CANCELADA = 'cancelada';

    protected $fillable = [
        'finca_id',
        'plan_id',
        'estado',
        'fecha_inicio',
        'fecha_vencimiento',
        'ultimo_pago_at',
        'monto_ultimo_pago',
        'referencia_pago',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_vencimiento' => 'date',
            'ultimo_pago_at' => 'datetime',
            'monto_ultimo_pago' => 'decimal:2',
        ];
    }

    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanSuscripcion::class, 'plan_id');
    }

    /**
     * Determina si la suscripción está vigente y con acceso habilitado.
     */
    public function estaVigente(): bool
    {
        if ($this->estado !== self::ESTADO_ACTIVA) {
            return false;
        }

        return $this->fecha_vencimiento->greaterThanOrEqualTo(now()->toDateString());
    }
}
