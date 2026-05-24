<?php

namespace Tests\Feature;

use App\Models\Lista;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecommendationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_recommendation_ranking_ordered_by_score(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Compra semanal',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

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

        $superCercano = Supermercado::query()->create([
            'nombre_super' => 'Cercano',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);
        $superLejano = Supermercado::query()->create([
            'nombre_super' => 'Lejano barato',
            'latitud' => 41.00000000,
            'longitud' => -3.00000000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $superCercano->id, 'precio' => 2.00],
            ['id_producto' => $pan->id, 'id_super' => $superCercano->id, 'precio' => 1.00],
            ['id_producto' => $leche->id, 'id_super' => $superLejano->id, 'precio' => 1.00],
            ['id_producto' => $pan->id, 'id_super' => $superLejano->id, 'precio' => 1.00],
        ]);

        $response = $this->actingAs($usuario)
            ->get(route('listas.recomendacion', $lista));

        $response->assertOk();
        $response->assertSeeTextInOrder(['Cercano', 'Lejano barato']);
        $response->assertSeeText('Ahorras');
        $response->assertSeeText('Lejano barato');
        $response->assertSeeText('Ver desglose de cesta');
        $response->assertSeeText('Leche');
        $response->assertSeeText('Pan');
    }

    public function test_recommendation_requires_access_to_lista(): void
    {
        $duenio = User::factory()->create();
        $invitado = User::factory()->create();

        $lista = Lista::query()->create([
            'nombre_lista' => 'Privada',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($duenio->id, ['permiso_lista' => 'owner']);

        $this->actingAs($invitado)
            ->get(route('listas.recomendacion', $lista))
            ->assertForbidden();
    }

    public function test_shows_empty_state_when_recommendation_is_not_available(): void
    {
        $usuario = User::factory()->create([
            'latitud' => null,
            'longitud' => null,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Sin ubicacion',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        $response = $this->actingAs($usuario)
            ->get(route('listas.recomendacion', $lista));

        $response->assertOk();
        $response->assertSeeText('Sin recomendación disponible');
    }

    public function test_user_can_select_recommended_supermarket_and_it_is_persisted(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Con elección',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);

        DB::table('formadas')->insert([
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 1,
            'marcado' => false,
        ]);

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Elegible',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);

        DB::table('venden')->insert([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 1.50,
        ]);

        $this->actingAs($usuario)
            ->post(route('listas.recomendacion.elegir', $lista), [
                'combinacion' => sha1((string) $supermercado->id),
            ])
            ->assertRedirect(route('listas.finalizar.confirmar', $lista));

        $this->assertDatabaseHas('listas', [
            'id' => $lista->id,
            'id_supermercado_elegido' => $supermercado->id,
        ]);

        $lista->refresh();

        $this->assertSame([
            [
                'id_super' => $supermercado->id,
                'nombre_super' => 'Elegible',
                'distancia_km' => round((float) $lista->supermercados_recomendados_snapshot[0]['distancia_km'], 3),
                'coste_distancia' => round((float) $lista->supermercados_recomendados_snapshot[0]['coste_distancia'], 2),
                'items_cesta' => 1,
            ],
        ], $lista->supermercados_recomendados_snapshot);
    }

    public function test_cannot_select_recommendation_without_user_coordinates(): void
    {
        $usuario = User::factory()->create([
            'latitud' => null,
            'longitud' => null,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Sin coordenadas',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'No elegible',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);

        $this->actingAs($usuario)
            ->from(route('listas.recomendacion', $lista))
            ->post(route('listas.recomendacion.elegir', $lista), [
                'combinacion' => sha1((string) $supermercado->id),
            ])
            ->assertSessionHasErrors('ubicacion');
    }

    public function test_user_can_select_multi_supermarket_recommendation_and_it_is_persisted(): void
    {
        $usuario = User::factory()->create([
            'latitud' => 40.00000000,
            'longitud' => -3.00000000,
        ]);

        $lista = Lista::query()->create([
            'nombre_lista' => 'Mixta',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

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
            ['id_lista' => $lista->id, 'id_producto' => $leche->id, 'cantidad' => 1, 'marcado' => false],
            ['id_lista' => $lista->id, 'id_producto' => $pan->id, 'cantidad' => 1, 'marcado' => false],
        ]);

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
            ['id_producto' => $leche->id, 'id_super' => $superLeche->id, 'precio' => 1.50],
            ['id_producto' => $pan->id, 'id_super' => $superPan->id, 'precio' => 1.20],
        ]);

        $this->actingAs($usuario)
            ->get(route('listas.recomendacion', $lista))
            ->assertSeeText('2 supermercados combinados')
            ->assertSeeText('Super leche + Super pan');

        $this->actingAs($usuario)
            ->post(route('listas.recomendacion.elegir', $lista), [
                'combinacion' => sha1($superLeche->id.'-'.$superPan->id),
            ])
            ->assertRedirect(route('listas.finalizar.confirmar', $lista));

        $lista->refresh();

        $this->assertSame($superLeche->id, $lista->id_supermercado_elegido);
        $this->assertCount(2, $lista->supermercados_recomendados_snapshot ?? []);
    }
}
