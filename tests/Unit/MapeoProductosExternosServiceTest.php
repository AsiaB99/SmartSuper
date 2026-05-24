<?php

namespace Tests\Unit;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use App\Services\MapeoProductosExternosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapeoProductosExternosServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_maps_exact_match_by_name_brand_and_format(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite de oliva virgen extra Carbonell',
            'marca' => 'Carbonell',
            'formato' => 'Botella 1 l',
            'imagen' => null,
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Aceite de oliva virgen extra Carbonell',
            'marca' => 'Carbonell',
            'formato' => 'Botella',
            'tamano' => '1 l',
            'imagen' => 'https://example.test/aceite.jpg',
        ]);

        app(MapeoProductosExternosService::class)->generarSugerencias($externo);

        $externo->refresh();

        $this->assertSame(ProductoExterno::ESTADO_MAPEADO, $externo->mapeo_estado);
        $this->assertSame($producto->id, $externo->producto_id);
        $this->assertNotNull($externo->sugerencia_snapshot);
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'marca' => 'Carbonell',
            'formato' => 'Botella 1 l',
            'imagen' => 'https://example.test/aceite.jpg',
        ]);
    }

    public function test_matches_with_accents_and_case_noise(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz vaporizado SOS',
            'marca' => 'SOS',
            'formato' => 'Paquete 1 kg',
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'ARROZ VAPORIZADO SÓS',
            'marca' => 'sos',
            'formato' => 'paquete',
            'tamano' => '1 kg',
        ]);

        app(MapeoProductosExternosService::class)->generarSugerencias($externo);

        $externo->refresh();

        $this->assertSame(ProductoExterno::ESTADO_MAPEADO, $externo->mapeo_estado);
        $this->assertSame($producto->id, $externo->producto_id);
    }

    public function test_leaves_suggested_when_multiple_candidates_are_too_close(): void
    {
        $seccion = Seccion::factory()->create();
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera Puleva',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
        ]);
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche semidesnatada Puleva',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Leche Puleva',
            'marca' => 'Puleva',
            'formato' => 'Brick',
            'tamano' => '1 l',
        ]);

        app(MapeoProductosExternosService::class)->generarSugerencias($externo);

        $externo->refresh();

        $this->assertSame(ProductoExterno::ESTADO_SUGERIDO, $externo->mapeo_estado);
        $this->assertNull($externo->producto_id);
        $this->assertNotNull($externo->sugerencia_snapshot);
    }

    public function test_leaves_pending_when_no_candidate_is_useful(): void
    {
        $seccion = Seccion::factory()->create();
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pan integral',
            'marca' => 'Bimbo',
            'formato' => 'Bolsa 500 g',
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Detergente ultra',
            'marca' => 'Bosque Verde',
            'formato' => 'Botella',
            'tamano' => '2 l',
        ]);

        app(MapeoProductosExternosService::class)->generarSugerencias($externo);

        $externo->refresh();

        $this->assertSame(ProductoExterno::ESTADO_PENDIENTE, $externo->mapeo_estado);
        $this->assertNull($externo->producto_id);
        $this->assertNull($externo->sugerencia_snapshot);
    }

    public function test_can_create_internal_product_and_map_it(): void
    {
        $seccion = Seccion::factory()->create();
        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Yogur natural Danone',
            'marca' => 'Danone',
            'formato' => 'Pack 4',
        ]);

        $resultado = app(MapeoProductosExternosService::class)->crearYMapearProducto($externo, [
            'nombre_producto' => 'Yogur natural Danone',
            'id_seccion' => $seccion->id,
            'marca' => 'Danone',
            'formato' => 'Pack 4',
            'imagen' => 'https://example.test/yogur.jpg',
        ]);

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Yogur natural Danone',
            'id_seccion' => $seccion->id,
            'marca' => 'Danone',
            'formato' => 'Pack 4',
        ]);
        $this->assertSame(ProductoExterno::ESTADO_MAPEADO, $resultado->mapeo_estado);
        $this->assertNotNull($resultado->producto_id);
    }

    public function test_confirming_mapping_does_not_override_existing_canonical_fields(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Cafe molido natural',
            'marca' => 'Marca propia',
            'formato' => 'Paquete 500 g',
            'imagen' => 'https://example.test/cafe-interno.jpg',
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Cafe molido natural',
            'marca' => 'Marca externa',
            'formato' => 'Paquete',
            'tamano' => '250 g',
            'imagen' => 'https://example.test/cafe-externo.jpg',
        ]);

        app(MapeoProductosExternosService::class)->confirmarMapeo($externo, $producto);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'marca' => 'Marca propia',
            'formato' => 'Paquete 500 g',
            'imagen' => 'https://example.test/cafe-interno.jpg',
        ]);
    }

    public function test_confirming_mapping_fills_missing_canonical_fields(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Tomate frito estilo casero',
            'marca' => null,
            'formato' => null,
            'imagen' => null,
        ]);

        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Tomate frito estilo casero',
            'marca' => 'Hacendado',
            'formato' => 'Tarro',
            'tamano' => '560 g',
            'imagen' => 'https://example.test/tomate.jpg',
        ]);

        app(MapeoProductosExternosService::class)->confirmarMapeo($externo, $producto);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'marca' => 'Hacendado',
            'formato' => 'Tarro 560 g',
            'imagen' => 'https://example.test/tomate.jpg',
        ]);
    }
}
