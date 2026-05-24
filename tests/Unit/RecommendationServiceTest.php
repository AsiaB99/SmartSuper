<?php

namespace Tests\Unit;

use App\Models\Lista;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_ranking_when_lista_has_no_items(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Sin lineas',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario);

        $this->assertSame([], $ranking);
    }

    public function test_returns_empty_ranking_when_user_has_no_coordinates(): void
    {
        $usuario = User::factory()->create([
            'latitud' => null,
            'longitud' => null,
        ]);

        [$lista, $leche] = $this->crearListaConUnProducto();

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Super A',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);

        DB::table('venden')->insert([
            'id_producto' => $leche->id,
            'id_super' => $supermercado->id,
            'precio' => 1.50,
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario);

        $this->assertSame([], $ranking);
    }

    public function test_builds_multi_supermarket_recommendation_when_no_single_store_covers_all_products(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();

        $superLeche = Supermercado::query()->create([
            'nombre_super' => 'Super leche',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);
        $superPan = Supermercado::query()->create([
            'nombre_super' => 'Super pan',
            'latitud' => 40.00150000,
            'longitud' => -3.00150000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $superLeche->id, 'precio' => 1.00],
            ['id_producto' => $pan->id, 'id_super' => $superPan->id, 'precio' => 0.90],
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertCount(1, $ranking);
        $this->assertTrue($ranking[0]['es_combinada']);
        $this->assertSame([$superLeche->id, $superPan->id], $ranking[0]['ids_super']);
        $this->assertSame('Super leche + Super pan', $ranking[0]['nombre_super']);
    }

    public function test_orders_supermarkets_by_score_ascending(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();

        $superBarato = Supermercado::query()->create([
            'nombre_super' => 'Barato',
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);
        $superCaro = Supermercado::query()->create([
            'nombre_super' => 'Caro',
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $superBarato->id, 'precio' => 1.00],
            ['id_producto' => $pan->id, 'id_super' => $superBarato->id, 'precio' => 1.00],
            ['id_producto' => $leche->id, 'id_super' => $superCaro->id, 'precio' => 2.00],
            ['id_producto' => $pan->id, 'id_super' => $superCaro->id, 'precio' => 2.00],
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario);

        $this->assertCount(2, $ranking);
        $this->assertSame('Barato', $ranking[0]['nombre_super']);
        $this->assertSame('Caro', $ranking[1]['nombre_super']);
        $this->assertLessThan($ranking[1]['score'], $ranking[0]['score']);
    }

    public function test_includes_basket_detail_per_supermarket(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Detalle OK',
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $supermercado->id, 'precio' => 1.25],
            ['id_producto' => $pan->id, 'id_super' => $supermercado->id, 'precio' => 0.80],
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertCount(1, $ranking);
        $this->assertSame(2, $ranking[0]['items_cesta']);
        $this->assertCount(2, $ranking[0]['detalle_cesta']);
        $this->assertSame('Leche', $ranking[0]['detalle_cesta'][0]['nombre_producto']);
        $this->assertSame('Detalle OK', $ranking[0]['detalle_cesta'][0]['nombre_super']);
        $this->assertSame(2, $ranking[0]['detalle_cesta'][0]['cantidad']);
        $this->assertSame(2.5, $ranking[0]['detalle_cesta'][0]['subtotal']);
    }

    public function test_uses_chain_prices_for_physical_stores(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();
        $cadenaId = DB::table('cadenas_supermercados')->insertGetId([
            'nombre' => 'Mercadona',
            'nombre_normalizado' => 'mercadona',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supermercado = Supermercado::query()->create([
            'id_cadena' => $cadenaId,
            'nombre_super' => 'Mercadona Centro',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);

        DB::table('precios_cadena')->insert([
            ['id_producto' => $leche->id, 'id_cadena' => $cadenaId, 'precio' => 1.25],
            ['id_producto' => $pan->id, 'id_cadena' => $cadenaId, 'precio' => 0.75],
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertCount(1, $ranking);
        $this->assertSame($supermercado->id, $ranking[0]['id_super']);
        $this->assertSame(3.25, $ranking[0]['total_cesta']);
    }

    public function test_store_price_takes_priority_over_chain_price(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();
        $cadenaId = DB::table('cadenas_supermercados')->insertGetId([
            'nombre' => 'Mercadona',
            'nombre_normalizado' => 'mercadona',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supermercado = Supermercado::query()->create([
            'id_cadena' => $cadenaId,
            'nombre_super' => 'Mercadona Centro',
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        DB::table('precios_cadena')->insert([
            ['id_producto' => $leche->id, 'id_cadena' => $cadenaId, 'precio' => 10.00],
            ['id_producto' => $pan->id, 'id_cadena' => $cadenaId, 'precio' => 1.00],
        ]);
        DB::table('venden')->insert([
            'id_producto' => $leche->id,
            'id_super' => $supermercado->id,
            'precio' => 1.00,
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertCount(1, $ranking);
        $this->assertSame(3.00, $ranking[0]['total_cesta']);
    }

    public function test_excludes_inactive_supermarkets(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche] = $this->crearListaConUnProducto();

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Inactivo',
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
            'activo' => false,
        ]);

        DB::table('venden')->insert([
            'id_producto' => $leche->id,
            'id_super' => $supermercado->id,
            'precio' => 1.00,
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertSame([], $ranking);
    }

    /**
     * @return array{Lista, Producto}
     */
    private function crearListaConUnProducto(): array
    {
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista simple',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $leche = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);

        DB::table('formadas')->insert([
            'id_lista' => $lista->id,
            'id_producto' => $leche->id,
            'cantidad' => 1,
            'marcado' => false,
        ]);

        return [$lista, $leche];
    }

    /**
     * @return array{Lista, Producto, Producto}
     */
    private function crearListaConDosProductos(): array
    {
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista completa',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $leche = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);
        $pan = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pan',
        ]);

        DB::table('formadas')->insert([
            ['id_lista' => $lista->id, 'id_producto' => $leche->id, 'cantidad' => 2, 'marcado' => false],
            ['id_lista' => $lista->id, 'id_producto' => $pan->id, 'cantidad' => 1, 'marcado' => false],
        ]);

        return [$lista, $leche, $pan];
    }
}
