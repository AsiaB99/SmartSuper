<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_filas_validas_de_productos_y_precios(): void
    {
        $this->createCsv('secciones.csv', "nombre_seccion\nLácteos\n");
        $this->createCsv('supermercados.csv', "nombre_super,direccion,latitud,longitud,fuente_datos\nMercadona Centro,Calle 1,40.4,-3.7,osm\n");
        $this->createCsv('productos.csv', "codigo_barras,nombre_producto,marca,formato,cantidad_envase,unidad_envase,seccion,imagen,fuente_datos\n8480000000000,Leche Entera,Hacendado,1 L,1,l,Lácteos,,openfoodfacts\n");
        $this->createCsv('precios.csv', "codigo_barras,nombre_super,precio,precio_unidad,unidad_ref,moneda,fecha_precio,fuente_precio,url_origen\n8480000000000,Mercadona Centro,0.91,0.91,l,EUR,2026-05-02,manual,https://example.test/precio\n");

        $this->artisan('datos:importar', [
            'tipo' => 'secciones',
            'archivo' => $this->tmpPath('secciones.csv'),
        ])->assertSuccessful();
        $this->artisan('datos:importar', [
            'tipo' => 'supermercados',
            'archivo' => $this->tmpPath('supermercados.csv'),
        ])->assertSuccessful();
        $this->artisan('datos:importar', [
            'tipo' => 'productos',
            'archivo' => $this->tmpPath('productos.csv'),
        ])->assertSuccessful();
        $this->artisan('datos:importar', [
            'tipo' => 'precios',
            'archivo' => $this->tmpPath('precios.csv'),
        ])->assertSuccessful();

        $this->assertDatabaseHas('productos', [
            'codigo_barras' => '8480000000000',
            'nombre_producto' => 'Leche Entera',
            'fuente_datos' => 'openfoodfacts',
        ]);

        $this->assertDatabaseHas('venden', [
            'moneda' => 'EUR',
            'fuente_precio' => 'manual',
            'url_origen' => 'https://example.test/precio',
        ]);
        $this->assertDatabaseCount('venden', 1);
        $this->assertSame(
            '2026-05-02',
            \Illuminate\Support\Facades\DB::table('venden')->value('fecha_precio')
                ? \Carbon\Carbon::parse((string) \Illuminate\Support\Facades\DB::table('venden')->value('fecha_precio'))->toDateString()
                : null
        );
    }

    public function test_importacion_maneja_duplicados_actualizando_precio(): void
    {
        $seccion = Seccion::factory()->create(['nombre_seccion' => 'Lácteos']);
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'codigo_barras' => '8480000000001',
            'nombre_producto' => 'Leche Semidesnatada',
        ]);
        $supermercado = Supermercado::factory()->create(['nombre_super' => 'Carrefour Centro']);

        $this->createCsv('precios_duplicados.csv', "codigo_barras,nombre_super,precio,moneda\n8480000000001,Carrefour Centro,1.00,EUR\n8480000000001,Carrefour Centro,1.20,EUR\n");

        $this->artisan('datos:importar', [
            'tipo' => 'precios',
            'archivo' => $this->tmpPath('precios_duplicados.csv'),
        ])
            ->expectsOutputToContain('updated=1')
            ->assertSuccessful();

        $this->assertDatabaseHas('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 1.20,
        ]);
    }

    public function test_importacion_reporta_producto_inexistente_en_precios(): void
    {
        Supermercado::factory()->create(['nombre_super' => 'Dia Centro']);
        $this->createCsv('precios_producto_inexistente.csv', "codigo_barras,nombre_super,precio,moneda\n9999999999999,Dia Centro,2.00,EUR\n");

        $this->artisan('datos:importar', [
            'tipo' => 'precios',
            'archivo' => $this->tmpPath('precios_producto_inexistente.csv'),
        ])
            ->expectsOutputToContain('missing_producto=1')
            ->assertSuccessful();

        $this->assertDatabaseCount('venden', 0);
    }

    public function test_importacion_reporta_supermercado_inexistente_en_precios(): void
    {
        $seccion = Seccion::factory()->create();
        Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'codigo_barras' => '8480000000002',
        ]);

        $this->createCsv('precios_super_inexistente.csv', "codigo_barras,nombre_super,precio,moneda\n8480000000002,Super Fantasma,3.00,EUR\n");

        $this->artisan('datos:importar', [
            'tipo' => 'precios',
            'archivo' => $this->tmpPath('precios_super_inexistente.csv'),
        ])
            ->expectsOutputToContain('missing_supermercado=1')
            ->assertSuccessful();

        $this->assertDatabaseCount('venden', 0);
    }

    private function createCsv(string $name, string $content): void
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->tmpPath($name), $content);
    }

    private function tmpPath(string $name): string
    {
        return storage_path('framework/testing/'.$name);
    }
}
