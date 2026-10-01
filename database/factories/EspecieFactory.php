<?php

namespace Database\Factories;

use App\Models\Especie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Especie>
 */
class EspecieFactory extends Factory
{
    protected $model = Especie::class;

    public function definition(): array
    {
        return [
            'nombre_comun' => 'Mojarra Roja',
            'nombre_cientifico' => 'Oreochromis sp.',
            'familia' => 'Cichlidae',
            'clima' => 'cálido',
        ];
    }
}

