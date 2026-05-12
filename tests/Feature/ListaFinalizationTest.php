<?php

namespace Tests\Feature;

use App\Models\Lista;
use App\Models\Despensa;
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

    public function test_confirm_finalization_view_lists_only_editable_despensas(): void
    {
        $usuario = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Compra mensual',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'editor']);

        $despensaEditable = Despensa::query()->create([
            'nombre_despensa' => 'Despensa editable',
            'fecha_creacion' => now(),
        ]);
        $despensaSoloLectura = Despensa::query()->create([
            'nombre_despensa' => 'Despensa lectura',
            'fecha_creacion' => now(),
        ]);

        $despensaEditable->usuarios()->attach($usuario->id, ['permiso_despensa' => 'editor']);
        $despensaSoloLectura->usuarios()->attach($usuario->id, ['permiso_despensa' => 'viewer']);

        $this->actingAs($usuario)
            ->get(route('listas.finalizar.confirmar', $lista))
            ->assertOk()
            ->assertSeeText('Despensa editable')
            ->assertDontSeeText('Despensa lectura');
    }

    public function test_finalizing_with_despensa_adds_producto_quantities_to_stock(): void
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

        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Casa',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($usuario->id, ['permiso_despensa' => 'editor']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $leche = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);
        $arroz = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz',
        ]);

        DB::table('formadas')->insert([
            ['id_lista' => $lista->id, 'id_producto' => $leche->id, 'cantidad' => 2, 'marcado' => false],
            ['id_lista' => $lista->id, 'id_producto' => $arroz->id, 'cantidad' => 1, 'marcado' => false],
        ]);
        $despensa->productos()->attach($leche->id, ['stock' => 3]);

        $supermercado = Supermercado::query()->create([
            'nombre_super' => 'Super A',
            'latitud' => 40.00100000,
            'longitud' => -3.00100000,
        ]);
        DB::table('venden')->insert([
            ['id_producto' => $leche->id, 'id_super' => $supermercado->id, 'precio' => 1.50],
            ['id_producto' => $arroz->id, 'id_super' => $supermercado->id, 'precio' => 1.20],
        ]);

        $this->actingAs($usuario)
            ->post(route('listas.finalizar', $lista), [
                'id_despensa' => $despensa->id,
            ])
            ->assertRedirect(route('listas.recomendacion', $lista));

        $this->assertDatabaseHas('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $leche->id,
            'stock' => 5,
        ]);
        $this->assertDatabaseHas('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $arroz->id,
            'stock' => 1,
        ]);
    }
}
