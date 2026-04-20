<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use App\Models\Venden;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'rol' => 'cliente',
        ]);

        // Crear admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'rol' => 'admin',
        ]);

        // Crear secciones
        $secciones = [
            'Frutas y verduras',
            'Carnes y pescados',
            'Lácteos',
            'Bebidas',
            'Alimentos secos',
            'Congelados',
            'Higiene y limpieza',
            'Panadería',
        ];

        foreach ($secciones as $seccion) {
            Seccion::factory()->create(['nombre_seccion' => $seccion]);
        }

        // Crear supermercados con coordenadas reales (Buenos Aires)
        $supermercados = [
            [
                'nombre_super' => 'Carrefour Centro',
                'latitud' => -34.603722,
                'longitud' => -58.381592,
            ],
            [
                'nombre_super' => 'Disco Supermercado',
                'latitud' => -34.595764,
                'longitud' => -58.374850,
            ],
            [
                'nombre_super' => 'Jumbo',
                'latitud' => -34.585721,
                'longitud' => -58.378923,
            ],
            [
                'nombre_super' => 'Coto',
                'latitud' => -34.575829,
                'longitud' => -58.385102,
            ],
            [
                'nombre_super' => 'Walmart',
                'latitud' => -34.598174,
                'longitud' => -58.392916,
            ],
        ];

        foreach ($supermercados as $super) {
            Supermercado::factory()->create($super);
        }

        // Crear productos variados
        $productos = [
            // Frutas y verduras
            ['nombre_producto' => 'Manzanas Rojas', 'id_seccion' => 1],
            ['nombre_producto' => 'Plátanos', 'id_seccion' => 1],
            ['nombre_producto' => 'Tomates', 'id_seccion' => 1],
            ['nombre_producto' => 'Lechuga', 'id_seccion' => 1],
            ['nombre_producto' => 'Cebolla', 'id_seccion' => 1],
            // Carnes y pescados
            ['nombre_producto' => 'Pechuga de Pollo', 'id_seccion' => 2],
            ['nombre_producto' => 'Carne Molida', 'id_seccion' => 2],
            ['nombre_producto' => 'Salmón Fresco', 'id_seccion' => 2],
            ['nombre_producto' => 'Jamón Cocido', 'id_seccion' => 2],
            // Lácteos
            ['nombre_producto' => 'Leche Descremada', 'id_seccion' => 3],
            ['nombre_producto' => 'Queso Fresco', 'id_seccion' => 3],
            ['nombre_producto' => 'Yogur Natural', 'id_seccion' => 3],
            ['nombre_producto' => 'Manteca', 'id_seccion' => 3],
            // Bebidas
            ['nombre_producto' => 'Agua Mineral', 'id_seccion' => 4],
            ['nombre_producto' => 'Jugo Naranja', 'id_seccion' => 4],
            ['nombre_producto' => 'Café', 'id_seccion' => 4],
            ['nombre_producto' => 'Té', 'id_seccion' => 4],
            // Alimentos secos
            ['nombre_producto' => 'Arroz Integral', 'id_seccion' => 5],
            ['nombre_producto' => 'Pasta', 'id_seccion' => 5],
            ['nombre_producto' => 'Fideos', 'id_seccion' => 5],
            ['nombre_producto' => 'Aceite de oliva', 'id_seccion' => 5],
            ['nombre_producto' => 'Sal', 'id_seccion' => 5],
            // Congelados
            ['nombre_producto' => 'Milanesas de Carne', 'id_seccion' => 6],
            ['nombre_producto' => 'Espinaca Congelada', 'id_seccion' => 6],
            ['nombre_producto' => 'Papas Prefritas', 'id_seccion' => 6],
            // Higiene y limpieza
            ['nombre_producto' => 'Detergente', 'id_seccion' => 7],
            ['nombre_producto' => 'Jabón de Manos', 'id_seccion' => 7],
            ['nombre_producto' => 'Papel Higiénico', 'id_seccion' => 7],
            // Panadería
            ['nombre_producto' => 'Pan Blanco', 'id_seccion' => 8],
            ['nombre_producto' => 'Pan Integral', 'id_seccion' => 8],
            ['nombre_producto' => 'Facturas', 'id_seccion' => 8],
        ];

        foreach ($productos as $producto) {
            Producto::factory()->create($producto);
        }

        $todosProductos = Producto::query()->get(['id']);
        $todosSupermercados = Supermercado::query()->get(['id']);

        foreach ($todosProductos as $producto) {
            foreach ($todosSupermercados as $supermercado) {
                $precio = round((float) random_int(120, 1200) / 10, 2);

                Venden::query()->create([
                    'id_producto' => $producto->id,
                    'id_super' => $supermercado->id,
                    'precio' => $precio,
                    'precio_unidad' => $precio,
                    'unidad_ref' => 'unidad',
                ]);
            }
        }
    }
}
