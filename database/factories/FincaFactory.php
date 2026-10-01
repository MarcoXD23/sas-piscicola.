<?php

namespace Database\Factories;

use App\Models\Finca;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finca>
 */
class FincaFactory extends Factory
{
    protected $model = Finca::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Finca Principal',
            'codigo' => 'FINCA-01',
            'ubicacion' => 'Espinal, Tolima',
            'configuraciones' => Finca::DEFAULT_CONFIG,
        ];
    }
}

