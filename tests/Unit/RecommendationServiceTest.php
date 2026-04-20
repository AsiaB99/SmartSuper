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

    public function test_excludes_supermarket_without_all_required_products(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        [$lista, $leche, $pan] = $this->crearListaConDosProductos();

        $superCompleto = Supermercado::query()->create([
            'nombre_super' => 'Completo',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);
        $superIncompleto = Supermercado::query()->create([
            'nombre_super' => 'Incompleto',
            'latitud' => 40.00150000,
            'longitud' => -3.00150000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $superCompleto->id, 'precio' => 1.20],
            ['id_producto' => $pan->id, 'id_super' => $superCompleto->id, 'precio' => 0.90],
            ['id_producto' => $leche->id, 'id_super' => $superIncompleto->id, 'precio' => 1.00],
        ]);

        $ranking = app(RecommendationService::class)->recomendarSupermercados($lista, $usuario, 0.0);

        $this->assertCount(1, $ranking);
        $this->assertSame('Completo', $ranking[0]['nombre_super']);
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