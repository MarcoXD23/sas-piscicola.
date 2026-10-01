<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Carbon\Carbon;
use Database\Factories\PondFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pond extends Model
{
    /** @use HasFactory<PondFactory> */
    use BelongsToFinca;

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'finca_id',
        'especie_id',
        'es_policultivo',
        'name',
        'code',
        'tipo_estanque',
        'numero_lote',
        'alevinera_origen',
        'fingerlings_stocked',
        'stocked_at',
        'status',
        'fish_population',
        'average_weight',
        'biomass',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_policultivo' => 'boolean',
            'stocked_at' => 'date',
            'fingerlings_stocked' => 'integer',
            'fish_population' => 'integer',
            'average_weight' => 'decimal:2',
            'biomass' => 'decimal:2',
        ];
    }

    /**
     * Días transcurridos de cultivo desde la siembra de alevinos.
     */
    public function getDaysInCultureAttribute(): int
    {
        if (! $this->stocked_at) {
            return 0;
        }

        return max(0, (int) Carbon::parse($this->stocked_at)->diffInDays(now()));
    }

    /**
     * Meses transcurridos de cultivo.
     */
    public function getMonthsInCultureAttribute(): float
    {
        return round($this->days_in_culture / 30.417, 1);
    }

    /**
     * Retorna la ruta de la foto representativa de la especie principal sembrada en el estanque.
     */
    public function getFotoEspecieAttribute(): string
    {
        $nameLower = mb_strtolower($this->name);

        if (str_contains($nameLower, 'roja')) {
            return asset('images/peces/mojarra_roja.jpg');
        }
        if (str_contains($nameLower, 'mojarra') || str_contains($nameLower, 'tilapia')) {
            return asset('images/peces/mojarra_negra.jpg');
        }
        if (str_contains($nameLower, 'cachama')) {
            return asset('images/peces/cachama_blanca.jpg');
        }
        if (str_contains($nameLower, 'bocachico')) {
            return asset('images/peces/bocachico.jpg');
        }
        if (str_contains($nameLower, 'trucha')) {
            return asset('images/peces/trucha_arcoiris.jpg');
        }
        if (str_contains($nameLower, 'bagre') || str_contains($nameLower, 'yaque')) {
            return asset('images/peces/bagre_rayado.jpg');
        }

        if ($this->especie?->foto_url) {
            return asset($this->especie->foto_url);
        }

        return asset('images/peces/mojarra_roja.jpg');
    }

    /**
     * Updates the pond's biomass.
     * Biomass (kg) = (Population * Average Weight (g)) / 1000
     */
    public function updateBiomass(): void
    {
        if ($this->especiesDetalle()->exists()) {
            $detalles = $this->especiesDetalle()->get();
            $totalPop = (int) $detalles->sum('fish_population');
            $totalBiomass = (float) $detalles->sum('biomass');
            $this->fish_population = $totalPop;
            $this->biomass = round($totalBiomass, 2);
            if ($totalPop > 0) {
                $this->average_weight = round(($totalBiomass * 1000) / $totalPop, 2);
            }
            $this->save();
            return;
        }

        $population = $this->fish_population > 0 ? $this->fish_population : ($this->fingerlings_stocked ?? 0);
        $this->biomass = ($population * $this->average_weight) / 1000;
        $this->save();
    }

    /**
     * Smart Ration Calculator.
     * Calculates the daily feed amount based on the current biomass and feeding rate.
     *
     * @param  float  $feedingRatePercentage  The feeding rate percentage (e.g., 2.0 for 2%)
     * @return float Amount of feed to provide daily in kilograms
     */
    public function calculateDailyRation(float $feedingRatePercentage): float
    {
        $currentBiomass = ($this->fish_population * $this->average_weight) / 1000;

        return (float) ($currentBiomass * ($feedingRatePercentage / 100));
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'especie_id')->withDefault(function (Especie $especie, Pond $pond) {
            $especie->id = 1;
            $especie->nombre_comun = $pond->especie_nombre_fallback;
            $especie->nombre_cientifico = 'Oreochromis sp.';
            $especie->foto_url = 'images/peces/mojarra_roja.jpg';
        });
    }

    public function especiePrincipal(): BelongsTo
    {
        return $this->especie();
    }

    public function especiesDetalle(): HasMany
    {
        return $this->hasMany(PondEspecie::class, 'pond_id');
    }

    public function especies(): BelongsToMany
    {
        return $this->belongsToMany(Especie::class, 'pond_especies', 'pond_id', 'especie_id')
            ->withPivot(['fingerlings_stocked', 'fish_population', 'average_weight', 'biomass', 'finca_id'])
            ->withTimestamps();
    }

    public function getEsPolicultivoAttribute(): bool
    {
        if (! empty($this->attributes['es_policultivo'])) {
            return true;
        }

        if ($this->relationLoaded('especiesDetalle')) {
            return $this->especiesDetalle->count() > 1;
        }

        return $this->especiesDetalle()->count() > 1;
    }

    public function getEspecieNombreFallbackAttribute(): string
    {
        $nameLower = mb_strtolower($this->name ?? '');

        if (str_contains($nameLower, 'roja')) {
            return 'Mojarra Roja';
        }
        if (str_contains($nameLower, 'cachama')) {
            return 'Cachama Blanca';
        }
        if (str_contains($nameLower, 'bocachico')) {
            return 'Bocachico';
        }
        if (str_contains($nameLower, 'trucha')) {
            return 'Trucha Arcoíris';
        }
        if (str_contains($nameLower, 'bagre') || str_contains($nameLower, 'yaque')) {
            return 'Bagre Rayado';
        }

        return 'Mojarra Negra / Tilapia Nilótica';
    }

    public function getEspecieNombreAttribute(): string
    {
        return $this->especiePrincipal?->nombre_comun ?? $this->especie_nombre_fallback;
    }

    /**
     * Ración sugerida por defecto al 2.5% de biomasa para alimentación de campo.
     */
    public function getRacionSugeridaKgAttribute(): float
    {
        if ($this->biomass <= 0 && $this->fish_population > 0 && $this->average_weight > 0) {
            $biomass = ($this->fish_population * $this->average_weight) / 1000;
        } else {
            $biomass = (float) $this->biomass;
        }

        return round($biomass * 0.025, 1);
    }

    public function samplings(): HasMany
    {
        return $this->hasMany(PondSampling::class, 'pond_id');
    }

    public function feedingLogs(): HasMany
    {
        return $this->hasMany(FeedingLog::class, 'pond_id');
    }

    public function harvestOrders(): HasMany
    {
        return $this->hasMany(HarvestOrder::class, 'pond_id');
    }

    public function bitacorasNocturnas(): HasMany
    {
        return $this->hasMany(BitacoraNocturna::class, 'estanque_id');
    }

    public function controlesAireadores(): HasMany
    {
        return $this->hasMany(ControlAireador::class, 'estanque_id');
    }

    public function aireadorActivo()
    {
        return $this->hasOne(ControlAireador::class, 'estanque_id')->whereNull('hora_apagado')->latestOfMany();
    }

    public function trasladosOrigen(): HasMany
    {
        return $this->hasMany(TrasladoPeces::class, 'estanque_origen_id');
    }

    public function trasladosDestino(): HasMany
    {
        return $this->hasMany(TrasladoPeces::class, 'estanque_destino_id');
    }

    public function tratamientosSanitarios(): HasMany
    {
        return $this->hasMany(TratamientoSanitario::class, 'estanque_id');
    }

    public function calidadAguas(): HasMany
    {
        return $this->hasMany(RegistroCalidadAgua::class, 'estanque_id');
    }

    public function mortalidades(): HasMany
    {
        return $this->hasMany(RegistroMortalidad::class, 'estanque_id');
    }

    /**
     * Retorna el tratamiento sanitario activo que impone tiempo de retiro en la fecha indicada.
     */
    public function tratamientoEnRetiroActivo(?Carbon $fecha = null): ?TratamientoSanitario
    {
        $targetDate = ($fecha ?? now())->toDateString();

        return $this->tratamientosSanitarios()
            ->where(function ($q) use ($targetDate) {
                $q->where('fecha_habil_cosecha', '>=', $targetDate)
                    ->orWhere('fecha_fin_retiro', '>=', $targetDate);
            })
            ->orderBy('fecha_fin_retiro', 'desc')
            ->first();
    }

    /**
     * Indica si el estanque se encuentra bloqueado por período de carencia o tiempo de retiro sanitario.
     */
    public function estaEnTiempoRetiro(?Carbon $fecha = null): bool
    {
        return $this->tratamientoEnRetiroActivo($fecha) !== null;
    }

    /**
     * Retorna la fecha límite formateada del tiempo de retiro activo.
     */
    public function fechaFinRetiroActivo(?Carbon $fecha = null): ?string
    {
        $tratamiento = $this->tratamientoEnRetiroActivo($fecha);

        return $tratamiento && $tratamiento->fecha_fin_retiro
            ? $tratamiento->fecha_fin_retiro->format('d/m/Y')
            : null;
    }
}
