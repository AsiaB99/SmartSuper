<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use App\Models\Venden;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_precios_index_in_read_only_mode(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 10.50,
            'precio_unidad' => 10.50,
            'unidad_ref' => 'unidad',
        ]);

        $response = $this->get(route('precios.index'));

        $response->assertOk();
        $response->assertSeeText('Encuentra el mejor precio');
        $response->assertSeeText('Busca un producto para empezar');
        $response->assertDontSee(route('admin.precios.create'), false);
        $response->assertDontSeeText('Nuevo precio');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
        $response->assertDontSeeText($producto->nombre_producto);
    }

    public function test_non_admin_user_can_access_precios_index_in_read_only_mode(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 10.50,
            'precio_unidad' => 10.50,
            'unidad_ref' => 'unidad',
        ]);

        $response = $this->actingAs($user)->get(route('precios.index'));

        $response->assertOk();
        $response->assertDontSee(route('admin.precios.create'), false);
        $response->assertDontSeeText('Nuevo precio');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_admin_can_view_precios_index(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 10.50,
            'precio_unidad' => 10.50,
            'unidad_ref' => 'unidad',
        ]);

        $response = $this->actingAs($admin)->get(route('precios.index'));

        $response->assertOk();
        $response->assertDontSee(route('admin.precios.create'), false);
        $response->assertDontSeeText($producto->nombre_producto);
    }

    public function test_precios_index_does_not_preselect_any_product_without_search(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);

        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => Supermercado::factory()->create()->id,
            'precio' => 1.50,
        ]);

        $response = $this->get(route('precios.index'));

        $response->assertOk();
        $response->assertSeeText('Selecciona un producto');
        $response->assertSeeText('Busca primero un producto para ver');
        $response->assertSeeText('Esperando búsqueda');
        $response->assertDontSeeText('Leche entera');
    }

    public function test_precios_index_shows_canonical_fallbacks_and_placeholder_image(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz redondo',
            'marca' => null,
            'formato' => null,
            'imagen' => null,
        ]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 2.35,
        ]);

        $response = $this->get(route('precios.index', ['busqueda' => 'Arroz', 'producto' => $producto->id]));

        $response->assertOk();
        $response->assertSeeText('Marca no disponible');
        $response->assertSeeText('Formato no informado');
        $response->assertSee('img/productos/placeholder.svg', false);
    }

    public function test_precios_index_uses_chain_prices_when_store_prices_are_missing(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite cadena',
        ]);

        $cadenaId = \App\Models\CadenaSupermercado::query()->create([
            'nombre' => 'Mercadona',
            'nombre_normalizado' => 'mercadona',
        ])->id;

        $supermercado = Supermercado::factory()->create([
            'id_cadena' => $cadenaId,
            'nombre_super' => 'Mercadona Centro',
            'activo' => true,
        ]);

        DB::table('precios_cadena')->insert([
            'id_producto' => $producto->id,
            'id_cadena' => $cadenaId,
            'precio' => 4.55,
            'precio_unidad' => 2.28,
            'unidad_ref' => 'l',
            'fecha_actualizacion' => now(),
        ]);

        $response = $this->get(route('precios.index', ['busqueda' => 'Aceite', 'producto' => $producto->id]));

        $response->assertOk();
        $response->assertSeeText('Mercadona');
        $response->assertSeeText('4,55');
    }

    public function test_precios_index_returns_rendered_partials_for_ajax_search(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Tomate pera',
            'marca' => 'Huerta',
            'formato' => '1 kg',
        ]);
        $cadenaId = \App\Models\CadenaSupermercado::query()->create([
            'nombre' => 'Super Uno',
            'nombre_normalizado' => 'super-uno',
        ])->id;
        $supermercado = Supermercado::factory()->create([
            'id_cadena' => $cadenaId,
            'nombre_super' => 'Super Uno Centro',
            'activo' => true,
        ]);

        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 3.25,
            'precio_unidad' => 3.25,
            'unidad_ref' => 'kg',
        ]);

        $response = $this
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->get(route('precios.index', ['busqueda' => 'Tomate']));

        $response->assertOk();
        $response->assertJsonPath('busqueda', 'Tomate');
        $response->assertJsonPath('page', 1);
        $response->assertJsonPath('productoId', $producto->id);
        $response->assertJsonStructure(['page', 'productosHtml', 'comparadorHtml']);
        $response->assertSee('Tomate pera');
        $response->assertSee('Super Uno');
    }

    public function test_precios_index_search_matches_brand_and_format(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
            'marca' => 'Marca Blanca',
            'formato' => '6 x 1 l',
        ]);
        $supermercado = Supermercado::factory()->create();

        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 5.40,
        ]);

        $this->get(route('precios.index', ['busqueda' => 'Blanca']))
            ->assertOk()
            ->assertSeeText('Leche entera');

        $this->get(route('precios.index', ['busqueda' => '6 x 1 l']))
            ->assertOk()
            ->assertSeeText('Leche entera');
    }

    public function test_precios_index_prioritizes_products_whose_name_starts_with_search_text(): void
    {
        $seccion = Seccion::factory()->create();

        $productoContiene = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Chocolate con leche',
        ]);

        $productoEmpieza = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);

        Venden::query()->create([
            'id_producto' => $productoContiene->id,
            'id_super' => Supermercado::factory()->create()->id,
            'precio' => 2.90,
        ]);

        Venden::query()->create([
            'id_producto' => $productoEmpieza->id,
            'id_super' => Supermercado::factory()->create()->id,
            'precio' => 1.50,
        ]);

        $response = $this
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->get(route('precios.index', ['busqueda' => 'leche']));

        $response->assertOk();
        $response->assertJsonPath('productoId', $productoEmpieza->id);
        $response->assertSeeInOrder(['Leche entera', 'Chocolate con leche']);
    }

    public function test_precios_index_matches_multi_word_search_across_name_and_brand(): void
    {
        $seccion = Seccion::factory()->create();

        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Yogur natural azucarado',
            'marca' => 'Danone',
        ]);

        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => Supermercado::factory()->create()->id,
            'precio' => 2.10,
        ]);

        $this->get(route('precios.index', ['busqueda' => 'yogur Danone']))
            ->assertOk()
            ->assertSeeText('Yogur natural azucarado');
    }

    public function test_precios_index_paginates_product_catalog(): void
    {
        $seccion = Seccion::factory()->create();

        foreach (range(1, 7) as $numero) {
            $producto = Producto::factory()->create([
                'id_seccion' => $seccion->id,
                'nombre_producto' => sprintf('Producto %02d', $numero),
            ]);

            Venden::query()->create([
                'id_producto' => $producto->id,
                'id_super' => Supermercado::factory()->create()->id,
                'precio' => 1 + $numero,
            ]);
        }

        $this->get(route('precios.index', ['busqueda' => 'Producto']))
            ->assertOk()
            ->assertSeeText('Producto 01')
            ->assertDontSeeText('Producto 07')
            ->assertSee('busqueda=Producto&amp;page=2', false);

        $this->get(route('precios.index', ['busqueda' => 'Producto', 'page' => 2]))
            ->assertOk()
            ->assertSeeText('Producto 07');
    }

    public function test_precios_index_shows_cheapest_chain_for_each_product_card(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Cafe molido',
        ]);

        $cadenaCaraId = \App\Models\CadenaSupermercado::query()->create([
            'nombre' => 'Cadena Cara',
            'nombre_normalizado' => 'cadena-cara',
        ])->id;

        $cadenaBarataId = \App\Models\CadenaSupermercado::query()->create([
            'nombre' => 'Cadena Barata',
            'nombre_normalizado' => 'cadena-barata',
        ])->id;

        DB::table('precios_cadena')->insert([
            [
                'id_producto' => $producto->id,
                'id_cadena' => $cadenaCaraId,
                'precio' => 4.80,
                'fecha_actualizacion' => now(),
            ],
            [
                'id_producto' => $producto->id,
                'id_cadena' => $cadenaBarataId,
                'precio' => 3.95,
                'fecha_actualizacion' => now(),
            ],
        ]);

        $response = $this->get(route('precios.index', ['busqueda' => 'Cafe', 'producto' => $producto->id]));

        $response->assertOk();
        $response->assertSeeText('Más barato en Cadena Barata');
        $response->assertSeeText('3,95');
    }

    public function test_admin_can_create_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.precios.store'), [
                'id_producto' => $producto->id,
                'id_super' => $supermercado->id,
                'precio' => 12.99,
                'precio_unidad' => 6.50,
                'unidad_ref' => 'kg',
            ]);

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseHas('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 12.99,
            'precio_unidad' => 6.50,
            'unidad_ref' => 'kg',
        ]);
    }

    public function test_admin_can_update_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 15.00,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.precios.update', [$producto->id, $supermercado->id]), [
                'precio' => 18.49,
                'precio_unidad' => 9.25,
                'unidad_ref' => 'litro',
            ]);

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseHas('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 18.49,
            'precio_unidad' => 9.25,
            'unidad_ref' => 'litro',
        ]);
    }

    public function test_admin_can_delete_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 22.00,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.precios.destroy', [$producto->id, $supermercado->id]));

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseMissing('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
        ]);
    }

    public function test_duplicate_producto_and_supermercado_pair_is_rejected(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 13.00,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.precios.store'), [
                'id_producto' => $producto->id,
                'id_super' => $supermercado->id,
                'precio' => 15.20,
            ]);

        $response->assertSessionHasErrors('id_producto');
    }

    public function test_non_admin_user_cannot_access_admin_precio_management_routes(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 15.00,
        ]);

        $this->actingAs($user)->get(route('admin.precios.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.precios.store'), [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 12.99,
        ])->assertForbidden();
        $this->actingAs($user)->get(route('admin.precios.edit', [$producto->id, $supermercado->id]))->assertForbidden();
        $this->actingAs($user)->put(route('admin.precios.update', [$producto->id, $supermercado->id]), [
            'precio' => 18.49,
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.precios.destroy', [$producto->id, $supermercado->id]))->assertForbidden();
    }
}
