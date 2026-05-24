<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoDemoCatalogCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_legacy_seed_products_as_demo(): void
    {
        $seccion = Seccion::factory()->create();
        $demo = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pan Blanco',
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ]);
        $real = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite de oliva virgen extra Carbonell',
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ]);

        $this->artisan('productos:marcar-demo-existentes')
            ->expectsOutput('Productos marcados como demo: 1.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos', [
            'id' => $demo->id,
            'origen_catalogo' => Producto::ORIGEN_DEMO,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $real->id,
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ]);
    }

    public function test_mapping_ignores_products_marked_as_demo(): void
    {
        $seccion = Seccion::factory()->create();

        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Cafe',
            'marca' => null,
            'formato' => null,
            'origen_catalogo' => Producto::ORIGEN_DEMO,
        ]);

        $real = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Cafe molido natural',
            'marca' => 'Marcilla',
            'formato' => 'Paquete 250 g',
            'origen_catalogo' => Producto::ORIGEN_MANUAL,
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Cafe molido natural',
            'marca' => 'Marcilla',
            'formato' => 'Paquete',
            'tamano' => '250 g',
        ]);

        app(\App\Services\MapeoProductosExternosService::class)->generarSugerencias($externo);

        $this->assertDatabaseHas('productos_externos', [
            'id' => $externo->id,
            'producto_id' => $real->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);
    }
}
