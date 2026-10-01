<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TrasladoPeces extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'traslados_peces';

    public const MOTIVO_DESDOBLE_DENSIDAD = 'desdoble_densidad';

    public const MOTIVO_CAMBIO_ETAPA = 'cambio_etapa';

    public const MOTIVO_LIMPIEZA_ESTANQUE = 'limpieza_estanque';

    protected $fillable = [
        'finca_id',
        'estanque_origen_id',
        'estanque_destino_id',
        'user_id',
        'fecha',
        'cantidad_peces_trasladados',
        'peso_promedio_gramos',
        'merma_traslado_peces',
        'motivo',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad_peces_trasladados' => 'integer',
            'peso_promedio_gramos' => 'decimal:2',
            'merma_traslado_peces' => 'integer',
        ];
    }

    public function estanqueOrigen(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_origen_id');
    }

    public function estanqueDestino(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Aplica el traslado de peces y recálculo de biomasas entre estanques en una transacción segura.
     */
    public function aplicarTraslado(): void
    {
        DB::transaction(function () {
            $origen = $this->estanqueOrigen()->lockForUpdate()->firstOrFail();
            $destino = $this->estanqueDestino()->lockForUpdate()->firstOrFail();

            // 1. Restar del estanque origen
            $origen->fish_population = max(0, (int) $origen->fish_population - $this->cantidad_peces_trasladados);
            if ($origen->fish_population === 0) {
                $origen->status = 'cosechado';
                $origen->biomass = 0;
            } else {
                $origen->biomass = max(0.0, round(($origen->fish_population * (float) $origen->average_weight) / 1000, 2));
            }
            $origen->save();

            // 2. Sumar al estanque destino descontando merma por manejo
            $pecesEfectivos = max(0, (int) $this->cantidad_peces_trasladados - (int) $this->merma_traslado_peces);
            $destino->fish_population = (int) $destino->fish_population + $pecesEfectivos;
            $destino->average_weight = (float) $this->peso_promedio_gramos;

            // Si el estanque estaba vacío o inactivo, actualizar estado a Sembrado
            if (in_array(strtolower($destino->status ?? ''), ['vacio', 'inactivo', 'cosechado', 'limpieza', ''])) {
                $destino->status = 'Sembrado';
                if (! $destino->stocked_at) {
                    $destino->stocked_at = $this->fecha;
                }
            }
            $destino->biomass = max(0.0, round(($destino->fish_population * (float) $destino->average_weight) / 1000, 2));
            $destino->save();
        });
    }
}
