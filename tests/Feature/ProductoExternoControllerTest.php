<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductoExternoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_cannot_access_external_mapping_panel(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($user)
            ->get(route('admin.productos-externos.index'))
            ->assertForbidden();
    }

    public function test_admin_can_filter_external_products_by_source_and_status(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        ProductoExterno::factory()->create([
            'nombre' => 'Aceite uno',
            'fuente' => 'mercadona',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
        ]);
        ProductoExterno::factory()->create([
            'nombre' => 'Aceite dos',
            'fuente' => 'carrefour',
            'mapeo_estado' => ProductoExterno::ESTADO_DESCARTADO,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.productos-externos.index', [
            'fuente' => 'mercadona',
            'estado' => ProductoExterno::ESTADO_PENDIENTE,
        ]));

        $response->assertOk();
        $response->assertSeeText('Aceite uno');
        $response->assertDontSeeText('Aceite dos');
    }

    public function test_admin_can_confirm_mapping_to_existing_product(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera Puleva',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
        ]);
        $externo = ProductoExterno::factory()->create([
            'mapeo_estado' => ProductoExterno::ESTADO_SUGERIDO,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.productos-externos.confirmar', $externo), [
            'producto_id' => $producto->id,
        ]);

        $response->assertRedirect(route('admin.productos-externos.index'));
        $this->assertDatabaseHas('productos_externos', [
            'id' => $externo->id,
            'producto_id' => $producto->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);
    }

    public function test_admin_can_create_internal_product_and_map_external_one(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $externo = ProductoExterno::factory()->create([
            'nombre' => 'Cafe molido natural',
            'marca' => 'Marcilla',
            'formato' => 'Paquete 250 g',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.productos-externos.store', $externo), [
            'nombre_producto' => 'Cafe molido natural',
            'id_seccion' => $seccion->id,
            'marca' => 'Marcilla',
            'formato' => 'Paquete 250 g',
            'imagen' => 'https://example.test/cafe.jpg',
        ]);

        $response->assertRedirect(route('admin.productos-externos.index'));
        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Cafe molido natural',
            'id_seccion' => $seccion->id,
            'marca' => 'Marcilla',
        ]);
        $this->assertDatabaseHas('productos_externos', [
            'id' => $externo->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);
    }

    public function test_admin_can_discard_external_product(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $externo = ProductoExterno::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.productos-externos.descartar', $externo));

        $response->assertRedirect(route('admin.productos-externos.index'));
        $this->assertDatabaseHas('productos_externos', [
            'id' => $externo->id,
            'mapeo_estado' => ProductoExterno::ESTADO_DESCARTADO,
        ]);
    }

    public function test_import_flow_auto_maps_suggested_product_without_admin_confirmation(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
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

        $rutaDirectorio = storage_path('app/testing');
        File::ensureDirectoryExists($rutaDirectorio);
        $rutaArchivo = $rutaDirectorio.'/mapeo-externo.json';

        File::put($rutaArchivo, json_encode([
            'productos' => [
                [
                    'external_id' => '1001',
                    'nombre' => 'Leche Puleva',
                    'marca' => 'Puleva',
                    'formato' => 'Brick',
                    'tamano' => '1 l',
                    'codigo_postal' => '28001',
                    'warehouse_id' => '4410',
                    'fuente' => 'mercadona',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('mercadona:importar-json', ['file' => $rutaArchivo])
            ->assertExitCode(0);

        $externo = ProductoExterno::query()->where('external_id', '1001')->firstOrFail();

        $this->assertDatabaseHas('productos_externos', [
            'id' => $externo->id,
            'producto_id' => $producto->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);
    }
}
