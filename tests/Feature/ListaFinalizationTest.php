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

class ListaFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalizing_a_lista_marks_it_as_comprada_and_redirects_to_recommendation(): void
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
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);

        DB::table('formadas')->insert([
            ['id_lista' => $lista->id, 'id_producto' => $producto->id, 'cantidad' => 1, 'marcado' => false],
        ]);

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Super A',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);

        DB::table('venden')->insert([
            ['id_producto' => $producto->id, 'id_super' => $supermercado->id, 'precio' => 1.50],
        ]);

        $response = $this->actingAs($usuario)
            ->post(route('listas.finalizar', $lista));

        $response->assertRedirect(route('listas.recomendacion', $lista));
        $this->assertDatabaseHas('listas', [
            'id' => $lista->id,
            'estado' => 'comprada',
        ]);
    }

    public function test_finalizing_requires_update_permission(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista privada',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $this->actingAs($viewer)
            ->post(route('listas.finalizar', $lista))
            ->assertForbidden();
    }
}