<?php

namespace App\Services;

use App\Models\InventarioAlimento;
use App\Models\Pond;

class FeedingCalculationService
{
    public const UMBRAL_DIAS_ALERTA = 5;

    /**
     * Calcula la biomasa total en kilogramos.
     * Biomasa (kg) = (Población × Peso promedio en gramos) / 1000
     */
    public function calculateBiomass(int $population, float $averageWeightGrams): float
    {
        return round(($population * $averageWeightGrams) / 1000, 2);
    }

    /**
     * Sugiere la tasa óptima de alimentación (% de biomasa) de acuerdo al peso de los peces.
     */
    public function suggestFeedingRate(float $averageWeightGrams): float
    {
        return match (true) {
            $averageWeightGrams <= 5.0 => 10.0,
            $averageWeightGrams <= 20.0 => 7.0,
            $averageWeightGrams <= 50.0 => 4.5,
            $averageWeightGrams <= 150.0 => 3.5,
            $averageWeightGrams <= 300.0 => 2.5,
            default => 1.8,
        };
    }

    /**
     * Calcula la ración diaria para un estanque específico.
     */
    public function calculateDailyRation(Pond $pond, ?float $customRate = null): float
    {
        $population = $pond->fish_population > 0 ? $pond->fish_population : ($pond->fingerlings_stocked ?? 0);
        $weight = (float) ($pond->average_weight ?? 150.0);
        $biomass = $this->calculateBiomass($population, $weight);
        $rate = $customRate !== null ? (float) $customRate : $this->suggestFeedingRate($weight);

        return round($biomass * ($rate / 100), 2);
    }

    /**
     * Calcula la proyección de consumo diario total de alimento en la finca (o filtrado por tipo de concentrado).
     */
    public function calculateProjectedDailyConsumption(int|string $fincaId, ?string $tipoConcentrado = null): float
    {
        $ponds = Pond::where('finca_id', $fincaId)
            ->where(function ($q) {
                $q->where('fish_population', '>', 0)
                    ->orWhere('fingerlings_stocked', '>', 0);
            })
            ->get();

        $totalDailyKg = 0.0;
        foreach ($ponds as $pond) {
            $totalDailyKg += $this->calculateDailyRation($pond);
        }

        return round($totalDailyKg, 2);
    }

    /**
     * Evalúa el inventario de bodega, calculando los días de consumo proyectados y generando alertas automáticas.
     *
     * @return array<string, mixed>
     */
    public function evaluateInventoryStatus(InventarioAlimento $alimento, ?float $customDailyConsumption = null): array
    {
        $consumoDiario = $customDailyConsumption !== null && $customDailyConsumption > 0
            ? $customDailyConsumption
            : $this->calculateProjectedDailyConsumption($alimento->finca_id, $alimento->tipo_concentrado);

        // Si no hay peces cargados se asume un consumo base mínimo para no dividir por 0
        $consumoReferencia = $consumoDiario > 0 ? $consumoDiario : 10.0;
        $stockActual = (float) $alimento->stock_actual_kg;
        $stockMinimo = (float) $alimento->stock_minimo_alerta_kg;

        $diasRestantes = round($stockActual / $consumoReferencia, 1);
        $esCritico = $stockActual <= $stockMinimo || $diasRestantes <= self::UMBRAL_DIAS_ALERTA;

        return [
            'alimento_id' => $alimento->id,
            'tipo_concentrado' => $alimento->tipo_concentrado,
            'stock_actual_kg' => $stockActual,
            'stock_minimo_alerta_kg' => $stockMinimo,
            'consumo_diario_proyectado_kg' => $consumoDiario,
            'dias_consumo_restantes' => $diasRestantes,
            'alerta_critica' => $esCritico,
            'nivel_alerta' => match (true) {
                $stockActual <= 0 => 'AGOTADO',
                $diasRestantes <= 2 => 'EMERGENCIA',
                $esCritico => 'CRITICO',
                default => 'NORMAL',
            },
            'mensaje_alerta' => $esCritico
                ? "ALERTA BODEGA: Stock de {$alimento->tipo_concentrado} en nivel crítico. Quedan {$diasRestantes} días de ración ({$stockActual} kg)."
                : 'Inventario de alimento en nivel óptimo de abastecimiento.',
        ];
    }
}
