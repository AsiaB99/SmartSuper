<?php

namespace Database\Factories;

use App\Models\ProductoExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoExternoFactory extends Factory
{
    protected $model = ProductoExterno::class;

    public function definition(): array
    {
        return [
            'fuente' => 'mercadona',
            'external_id' => fake()->unique()->numerify('EXT###'),
            'nombre' => fake()->unique()->words(3, asText: true),
            'marca' => fake()->company(),
            'formato' => fake()->randomElement(['Botella', 'Paquete', 'Caja']),
            'precio' => fake()->randomFloat(2, 0.5, 25),
            'precio_anterior' => null,
            'precio_unidad' => fake()->randomFloat(2, 0.1, 10),
            'unidad_ref' => fake()->randomElement(['kg', 'l', 'ud']),
            'tamano' => fake()->randomElement(['1 l', '500 g', '6 ud']),
            'imagen' => fake()->imageUrl(),
            'url_producto' => fake()->url(),
            'disponible' => true,
            'codigo_postal' => '28001',
            'warehouse_id' => '4410',
            'categoria_id' => fake()->numerify('CAT###'),
            'categoria_nombre' => fake()->word(),
            'payload' => ['raw' => true],
            'fecha_importacion' => now(),
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
            'sugerencia_score' => null,
            'sugerencia_snapshot' => null,
            'producto_id' => null,
        ];
    }
}
