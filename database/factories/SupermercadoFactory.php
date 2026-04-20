<?php

namespace Database\Factories;

use App\Models\Supermercado;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupermercadoFactory extends Factory
{
    protected $model = Supermercado::class;

    public function definition(): array
    {
        return [
            'nombre_super' => fake()->unique()->company(),
            'latitud' => fake()->latitude(),
            'longitud' => fake()->longitude(),
        ];
    }
}
