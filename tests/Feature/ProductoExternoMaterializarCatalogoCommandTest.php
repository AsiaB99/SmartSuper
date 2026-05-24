<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoExternoMaterializarCatalogoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_materializa_pendientes_sugeridos_y_sincroniza_mapeados(): void
    {
        $seccion = Seccion::factory()->create([
            'nombre_seccion' => 'Lacteos',
        ]);

        $productoExistente = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ]);

        $pendiente = ProductoExterno::factory()->create([
            'nombre' => 'Tomate frito',
            'marca' => 'Hacendado',
            'formato' => 'Tarro',
            'tamano' => '560 g',
            'categoria_nombre' => 'Conservas',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
            'producto_id' => null,
            'precio' => 1.45,
            'fuente' => 'mercadona',
        ]);

        $sugerido = ProductoExterno::factory()->create([
            'nombre' => 'Leche entera Puleva',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
            'mapeo_estado' => ProductoExterno::ESTADO_SUGERIDO,
            'producto_id' => null,
            'sugerencia_snapshot' => [
                'id' => $productoExistente->id,
                'nombre_producto' => $productoExistente->nombre_producto,
                'marca' => $productoExistente->marca,
                'formato' => $productoExistente->formato,
                'seccion' => 'Lacteos',
                'score' => 0.88,
            ],
            'precio' => 1.15,
            'fuente' => 'mercadona',
        ]);

        $mapeado = ProductoExterno::factory()->create([
            'nombre' => 'Leche entera Puleva promo',
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
            'producto_id' => $productoExistente->id,
            'precio' => 1.05,
            'fuente' => 'mercadona',
        ]);

        $this->artisan('productos-externos:materializar-catalogo', [
            '--batch' => 2,
        ])
            ->expectsOutputToContain('Materializados: 3 productos externos.')
            ->expectsOutputToContain('Mapeados: 3')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'id' => $pendiente->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Tomate frito',
            'marca' => 'Hacendado',
            'formato' => 'Tarro 560 g',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $this->assertDatabaseHas('productos_externos', [
            'id' => $sugerido->id,
            'producto_id' => $productoExistente->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);

        $this->assertDatabaseHas('precios_cadena', [
            'id_producto' => $productoExistente->id,
            'precio' => 1.05,
        ]);
    }

    public function test_materializa_producto_nuevo_limpiando_nombre_duplicado_del_formato(): void
    {
        ProductoExterno::factory()->create([
            'nombre' => 'Gel limpiador hidratante',
            'marca' => 'Deliplus',
            'formato' => 'Gel limpiador hidratante, 250 mililitros',
            'tamano' => null,
            'categoria_nombre' => 'Higiene',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
            'producto_id' => null,
            'fuente' => 'mercadona',
        ]);

        $this->artisan('productos-externos:materializar-catalogo')
            ->expectsOutputToContain('Materializados: 1 productos externos.')
            ->expectsOutputToContain('Mapeados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Gel limpiador hidratante',
            'marca' => 'Deliplus',
            'formato' => '250 mililitros',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);
    }

    public function test_materializa_producto_nuevo_en_seccion_canonica(): void
    {
        ProductoExterno::factory()->create([
            'nombre' => 'Comida húmeda gato adulto',
            'marca' => 'Compy',
            'categoria_nombre' => 'Gatos',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
            'producto_id' => null,
            'fuente' => 'mercadona',
        ]);

        $this->artisan('productos-externos:materializar-catalogo')
            ->expectsOutputToContain('Materializados: 1 productos externos.')
            ->expectsOutputToContain('Mapeados: 1')
            ->assertExitCode(0);

        $seccion = Seccion::query()->where('nombre_seccion', 'Mascotas')->firstOrFail();

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Comida húmeda gato adulto',
            'id_seccion' => $seccion->id,
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);
    }
}
