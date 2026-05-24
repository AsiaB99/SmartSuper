<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MercadonaImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_productos_externos_desde_json_normalizado(): void
    {
        $rutaDirectorio = storage_path('app/testing');
        File::ensureDirectoryExists($rutaDirectorio);

        $rutaArchivo = $rutaDirectorio.'/mercadona-normalizado.json';

        File::put($rutaArchivo, json_encode([
            'supermercado' => [
                'cadena' => 'Mercadona',
                'codigo_postal' => '04720',
                'warehouse_id' => '4410',
            ],
            'categoria' => [
                'id' => '112',
                'nombre' => 'Aceite, vinagre y sal',
            ],
            'productos' => [
                [
                    'external_id' => '4241',
                    'nombre' => 'Aceite de oliva 0,4º Hacendado',
                    'marca' => 'Hacendado',
                    'formato' => 'Garrafa',
                    'precio' => 18.75,
                    'precio_anterior' => 19.75,
                    'precio_unidad' => 3.75,
                    'unidad_ref' => 'L',
                    'tamano' => 'Garrafa 5 l',
                    'imagen' => 'https://example.test/aceite.jpg',
                    'url_producto' => 'https://tienda.mercadona.es/product/4241/demo',
                    'disponible' => true,
                    'codigo_postal' => '04720',
                    'warehouse_id' => '4410',
                    'categoria_id' => '420',
                    'categoria_nombre' => 'Aceite de oliva',
                    'fuente' => 'mercadona',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('mercadona:importar-json', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Materializados en catálogo: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'mercadona',
            'external_id' => '4241',
            'codigo_postal' => '04720',
            'warehouse_id' => '4410',
            'nombre' => 'Aceite de oliva 0,4º Hacendado',
            'precio' => 18.75,
            'precio_unidad' => 3.75,
            'mapeo_estado' => 'mapeado',
        ]);

        $this->assertDatabaseHas('secciones', [
            'nombre_seccion' => 'Aceite de oliva',
        ]);

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Aceite de oliva 0,4º Hacendado',
            'marca' => 'Hacendado',
            'formato' => 'Garrafa 5 l',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $this->assertDatabaseHas('cadenas_supermercados', [
            'nombre' => 'Mercadona',
            'nombre_normalizado' => 'mercadona',
        ]);

        $this->assertDatabaseHas('precios_cadena', [
            'precio' => 18.75,
            'precio_unidad' => 3.75,
            'unidad_ref' => 'L',
        ]);
    }
}
