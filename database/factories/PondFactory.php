<?php

namespace Database\Factories;

use App\Models\Pond;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pond>
 */
class PondFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $population = $this->faker->numberBetween(1000, 5000);
        $weight = $this->faker->randomFloat(2, 50, 500);

        return [
            'finca_id' => $this->faker->numberBetween(1, 10),
            'name' => 'Estanque '.$this->faker->unique()->numberBetween(1, 1000),
            'fish_population' => $population,
            'average_weight' => $weight,
            'biomass' => ($population * $weight) / 1000,
        ];
    }
}
