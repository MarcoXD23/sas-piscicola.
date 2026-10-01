<?php

namespace App\Models;

use App\Traits\BelongsToFinca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondSampling extends Model
{
    use BelongsToFinca;
    use HasFactory;

    protected $fillable = [
        'finca_id',
        'pond_id',
        'sampling_date',
        'sampled_fish_count',
        'sample_total_weight_kg',
        'average_weight_g',
        'small_count',
        'medium_count',
        'large_count',
        'commercial_count',
        'small_percent',
        'medium_percent',
        'large_percent',
        'commercial_percent',
        'biomass_estimate_kg',
        'registered_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sampling_date' => 'date',
            'sampled_fish_count' => 'integer',
            'sample_total_weight_kg' => 'decimal:3',
            'average_weight_g' => 'decimal:2',
            'small_count' => 'integer',
            'medium_count' => 'integer',
            'large_count' => 'integer',
            'commercial_count' => 'integer',
            'small_percent' => 'decimal:2',
            'medium_percent' => 'decimal:2',
            'large_percent' => 'decimal:2',
            'commercial_percent' => 'decimal:2',
            'biomass_estimate_kg' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PondSampling $sampling) {
            $sampling->recalculateMetrics();
        });

        static::saved(function (PondSampling $sampling) {
            // Actualizar el peso promedio y biomasa del estanque
            if ($sampling->pond && $sampling->average_weight_g > 0) {
                $sampling->pond->average_weight = $sampling->average_weight_g;
                $sampling->pond->updateBiomass();
            }
        });
    }

    /**
     * Calcula automáticamente el peso promedio, distribución de tallas y biomasa estimada.
     */
    public function recalculateMetrics(): void
    {
        if ($this->sampled_fish_count > 0 && $this->sample_total_weight_kg > 0) {
            $this->average_weight_g = round(($this->sample_total_weight_kg * 1000) / $this->sampled_fish_count, 2);
        }

        $totalCategorized = $this->small_count + $this->medium_count + $this->large_count + $this->commercial_count;
        if ($totalCategorized > 0) {
            $this->small_percent = round(($this->small_count / $totalCategorized) * 100, 2);
            $this->medium_percent = round(($this->medium_count / $totalCategorized) * 100, 2);
            $this->large_percent = round(($this->large_count / $totalCategorized) * 100, 2);
            $this->commercial_percent = round(($this->commercial_count / $totalCategorized) * 100, 2);
        }

        if ($this->pond_id) {
            $pond = $this->pond ?? Pond::find($this->pond_id);
            if ($pond) {
                $population = $pond->fish_population > 0 ? $pond->fish_population : ($pond->fingerlings_stocked ?? 0);
                if ($population > 0 && $this->average_weight_g > 0) {
                    $this->biomass_estimate_kg = round(($this->average_weight_g / 1000) * $population, 2);
                }
            }
        }
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }
}
