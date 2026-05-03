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
        $response->assertSeeText('Ahorro frente a');
        $response->assertSeeText('Lejano barato');
        $response->assertSeeText('Ver desglose de cesta (2 productos)');
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
        $response->assertSeeText('Sin recomendacion disponible');
    }
}