<?php

namespace Database\Factories;

use App\Models\Producto;
use App\Models\Seccion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre_producto' => fake()->unique()->words(3, asText: true),
            'id_seccion' => Seccion::inRandomOrder()->first()?->id ?? Seccion::factory(),
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ];
    }
}
