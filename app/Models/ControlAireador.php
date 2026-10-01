<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlAireador extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $table = 'control_aireadores';

    public const FUENTE_RED_ELECTRICA = 'red_electrica';

    public const FUENTE_PLANTA_EMERGENCIA = 'planta_emergencia';

    protected $fillable = [
        'finca_id',
        'estanque_id',
        'user_id',
        'fecha',
        'hora_encendido',
        'hora_apagado',
        'total_horas',
        'fuente_energia',
        'corte_luz',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_encendido' => 'datetime',
            'hora_apagado' => 'datetime',
            'total_horas' => 'float',
            'corte_luz' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ControlAireador $aireador) {
            if ($aireador->hora_encendido && $aireador->hora_apagado) {
                $inicio = Carbon::parse($aireador->hora_encendido);
                $fin = Carbon::parse($aireador->hora_apagado);
                $minutos = max(0, $inicio->diffInMinutes($fin));
                $aireador->total_horas = round($minutos / 60, 2);
            }
        });
    }

    /**
     * Minutos transcurridos desde que se encendió el aireador hasta ahora o hasta que se apagó.
     */
    public function getMinutosActivoAttribute(): int
    {
        if (! $this->hora_encendido) {
            return 0;
        }

        $fin = $this->hora_apagado ? Carbon::parse($this->hora_apagado) : now();

        return max(0, (int) Carbon::parse($this->hora_encendido)->diffInMinutes($fin));
    }

    /**
     * Tiempo formateado en horas y minutos (ej. "2h 30m" o "45 min").
     */
    public function getTiempoOperacionTextoAttribute(): string
    {
        $minutos = $this->minutos_activo;
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;

        if ($horas > 0) {
            return "{$horas}h {$mins}m";
        }

        return "{$mins} min";
    }

    /**
     * Estimación de consumo de combustible (aprox 0.6 gal/hora en planta diésel).
     */
    public function getConsumoDieselEstimadoGalonesAttribute(): float
    {
        if ($this->fuente_energia !== self::FUENTE_PLANTA_EMERGENCIA) {
            return 0.0;
        }

        return round(($this->minutos_activo / 60) * 0.6, 2);
    }

    /**
     * Apaga el aireador y calcula automáticamente el total de horas operadas.
     */
    public function apagar(?Carbon $horaApagado = null, ?string $observaciones = null): void
    {
        $fin = $horaApagado ?? now();
        $inicio = Carbon::parse($this->hora_encendido);
        $minutos = max(0, $inicio->diffInMinutes($fin));

        $this->update([
            'hora_apagado' => $fin,
            'total_horas' => round($minutos / 60, 2),
            'observaciones' => $observaciones ?? $this->observaciones,
        ]);
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
     * Scope para consultar aireadores actualmente en marcha (no apagados).
     */
    public function scopeActivos($query)
    {
        return $query->whereNull('hora_apagado');
    }

    /**
     * Scope para consultar registros operados con planta diésel.
     */
    public function scopePlantaDiesel($query)
    {
        return $query->where('fuente_energia', self::FUENTE_PLANTA_EMERGENCIA);
    }
}
