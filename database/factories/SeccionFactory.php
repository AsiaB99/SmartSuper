<?php

namespace Database\Factories;

use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeccionFactory extends Factory
{
    protected $model = Seccion::class;

    public function definition(): array
    {
        return [
            'nombre_seccion' => fake()->unique()->words(2, asText: true),
        ];
    }
}
