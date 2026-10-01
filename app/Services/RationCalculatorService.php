<?php

namespace App\Services;

use App\Models\FeedInventory;
use App\Models\Pond;

class RationCalculatorService
{
    /**
     * Calcula la biomasa total en kilogramos:
     * Biomasa (kg) = (Población × Peso promedio en gramos) / 1000
     */
    public function calculateBiomass(int $population, float $averageWeightGrams): float
    {
        return round(($population * $averageWeightGrams) / 1000, 2);
    }

    /**
     * Sugiere automáticamente la tasa de alimentación diaria (% de biomasa)
     * según la etapa de crecimiento y el peso promedio de los peces.
     *
     * Tablas estándar de acuicultura (Tilapia / Trucha):
     * - Alevines (< 5g): 10.0%
     * - Alevinaje (5g - 20g): 7.0%
     * - Levante inicial (20g - 50g): 4.5%
     * - Pre-engorde (50g - 150g): 3.5%
     * - Engorde fase 1 (150g - 300g): 2.5%
     * - Engorde final / Cosecha (> 300g): 1.8%
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
     * Retorna la cantidad recomendada de tomas o frecuencias de alimentación al día
     * según el tamaño de los peces.
     */
    public function suggestDailyPortions(float $averageWeightGrams): int
    {
        return match (true) {
            $averageWeightGrams <= 20.0 => 4, // 4 raciones al día
            $averageWeightGrams <= 150.0 => 3, // 3 raciones al día
            default => 2, // 2 raciones al día
        };
    }

    /**
     * Calcula la ración completa del estanque, devolviendo biomasa, porcentaje, ración diaria
     * y división en tomas.
     *
     * @return array<string, mixed>
     */
    public function calculateForPond(Pond $pond, ?float $customRate = null): array
    {
        $averageWeight = (float) $pond->average_weight;
        $population = (int) $pond->fish_population;

        // Aseguramos biomasa precisa
        $biomass = $this->calculateBiomass($population, $averageWeight);

        // Si el usuario especificó una tasa la usamos, sino sugerimos la óptima
        $rate = $customRate !== null ? (float) $customRate : $this->suggestFeedingRate($averageWeight);
        $dailyRation = round($biomass * ($rate / 100), 2);
        $portions = $this->suggestDailyPortions($averageWeight);
        $rationPerPortion = $portions > 0 ? round($dailyRation / $portions, 2) : $dailyRation;

        return [
            'pond_id' => $pond->id,
            'pond_name' => $pond->name,
            'fish_population' => $population,
            'average_weight_g' => $averageWeight,
            'biomass_kg' => $biomass,
            'feeding_rate_percentage' => $rate,
            'is_suggested_rate' => $customRate === null,
            'daily_ration_kg' => $dailyRation,
            'suggested_daily_portions' => $portions,
            'ration_per_portion_kg' => $rationPerPortion,
        ];
    }

    /**
     * Valida si un lote de alimento cuenta con stock suficiente para cubrir la ración requerida.
     *
     * @return array<string, mixed>
     */
    public function checkStock(FeedInventory $feed, float $requiredKg): array
    {
        $availableStock = (float) $feed->quantity_kg;
        $hasSufficient = $availableStock >= $requiredKg;
        $deficit = $hasSufficient ? 0.0 : round($requiredKg - $availableStock, 2);
        $remaining = $hasSufficient ? round($availableStock - $requiredKg, 2) : 0.0;

        return [
            'feed_id' => $feed->id,
            'feed_name' => $feed->name,
            'brand' => $feed->brand,
            'feed_type' => $feed->feed_type,
            'protein_percentage' => (float) $feed->protein_percentage,
            'bag_weight_kg' => (float) $feed->bag_weight_kg,
            'available_stock_kg' => $availableStock,
            'required_kg' => $requiredKg,
            'has_sufficient_stock' => $hasSufficient,
            'projected_remaining_stock_kg' => $remaining,
            'deficit_kg' => $deficit,
        ];
    }
}
