<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TratamientoSanitario extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'tratamientos_sanitarios';

    public const TIPO_BANO_SAL = 'bano_sal';

    public const TIPO_ENCALADO = 'encalado';

    public const TIPO_MEDICAMENTO_VETERINARIO = 'medicamento_veterinario';

    public const TIPO_DESINFECTANTE = 'desinfectante';

    protected $fillable = [
        'finca_id',
        'lote_id',
        'estanque_id',
        'user_id',
        'responsable',
        'fecha_aplicacion',
        'tipo_tratamiento',
        'producto',
        'principio_activo',
        'dosis',
        'dosis_aplicada',
        'tiempo_retiro_dias',
        'dias_tiempo_retiro',
        'fecha_habil_cosecha',
        'fecha_fin_retiro',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
            'fecha_fin_retiro' => 'date',
            'fecha_habil_cosecha' => 'date',
            'dias_tiempo_retiro' => 'integer',
            'tiempo_retiro_dias' => 'integer',
        ];
    }

    public function estanque(): BelongsTo
    {
        return $this->belongsTo(Pond::class, 'estanque_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sincronización automática de campos calculados y aliases ICA.
     */
    protected static function booted(): void
    {
        static::saving(function (TratamientoSanitario $tratamiento) {
            // Normalizar dosis
            if (empty($tratamiento->dosis) && ! empty($tratamiento->dosis_aplicada)) {
                $tratamiento->dosis = $tratamiento->dosis_aplicada;
            } elseif (! empty($tratamiento->dosis) && empty($tratamiento->dosis_aplicada)) {
                $tratamiento->dosis_aplicada = $tratamiento->dosis;
            }

            // Normalizar días de retiro
            $dias = $tratamiento->tiempo_retiro_dias ?? $tratamiento->dias_tiempo_retiro ?? 0;
            $tratamiento->tiempo_retiro_dias = $dias;
            $tratamiento->dias_tiempo_retiro = $dias;

            // Calcular fecha hábil de cosecha
            if ($tratamiento->fecha_aplicacion) {
                $fechaAplicacion = Carbon::parse($tratamiento->fecha_aplicacion);
                $fechaHabil = $fechaAplicacion->copy()->addDays((int) $dias);
                $tratamiento->fecha_habil_cosecha = $fechaHabil->toDateString();
                $tratamiento->fecha_fin_retiro = $fechaHabil->toDateString();
            }

            // Normalizar responsable
            if (empty($tratamiento->responsable) && $tratamiento->user) {
                $tratamiento->responsable = $tratamiento->user->name;
            }
        });
    }

    /**
     * Verifica si el tratamiento está actualmente en período de carencia/retiro para una fecha dada.
     */
    public function estaEnRetiro(?Carbon $fecha = null): bool
    {
        $fechaEvaluar = ($fecha ?? now())->toDateString();
        $limite = $this->fecha_habil_cosecha ?? $this->fecha_fin_retiro;

        return $limite && Carbon::parse($limite)->greaterThanOrEqualTo($fechaEvaluar);
    }

    /**
     * Indica si bloquea la cosecha o venta en la fecha especificada bajo la normativa del ICA.
     */
    public function bloqueaCosecha(?Carbon $fecha = null): bool
    {
        return $this->estaEnRetiro($fecha);
    }

    /**
     * Scope para filtrar tratamientos que tienen tiempo de retiro activo.
     */
    public function scopeEnRetiro(Builder $query, ?string $fecha = null): Builder
    {
        $targetDate = $fecha ?? now()->toDateString();

        return $query->where(function ($q) use ($targetDate) {
            $q->where('fecha_habil_cosecha', '>=', $targetDate)
                ->orWhere('fecha_fin_retiro', '>=', $targetDate);
        });
    }
}
