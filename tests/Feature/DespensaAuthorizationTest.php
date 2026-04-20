<?php

namespace Tests\Feature;

use App\Models\Despensa;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DespensaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_despensas(): void
    {
        $response = $this->get(route('despensas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_creating_a_despensa_assigns_owner_permission_to_current_user(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('despensas.store'), [
                'nombre_despensa' => 'Despensa casa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('despensas.index'));

        $despensa = Despensa::query()->where('nombre_despensa', 'Despensa casa')->firstOrFail();
        $this->assertDatabaseHas('tienen', [
            'id_usuario' => $usuario->id,
            'id_despensa' => $despensa->id,
            'permiso_despensa' => 'owner',
        ]);
    }

    public function test_viewer_cannot_update_a_despensa(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa base',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($viewer->id, ['permiso_despensa' => 'viewer']);

        $this->actingAs($viewer)
            ->put(route('despensas.update', $despensa), [
                'nombre_despensa' => 'Intento no permitido',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertForbidden();
    }

    public function test_editor_can_update_a_despensa(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa editable',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($editor->id, ['permiso_despensa' => 'editor']);

        $this->actingAs($editor)
            ->put(route('despensas.update', $despensa), [
                'nombre_despensa' => 'Despensa editada',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('despensas.index'));

        $this->assertDatabaseHas('despensas', [
            'id' => $despensa->id,
            'nombre_despensa' => 'Despensa editada',
        ]);
    }

    public function test_admin_can_delete_any_despensa(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa para borrar',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $this->actingAs($admin)
            ->delete(route('despensas.destroy', $despensa))
            ->assertRedirect(route('despensas.index'));

        $this->assertDatabaseMissing('despensas', ['id' => $despensa->id]);
    }

    public function test_editor_can_add_update_and_remove_stock_in_despensa(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa stock',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($editor->id, ['permiso_despensa' => 'editor']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz',
        ]);

        $this->actingAs($editor)
            ->post(route('despensas.stock.agregar', $despensa), [
                'id_producto' => $producto->id,
                'stock' => 3,
            ])
            ->assertRedirect(route('despensas.stock', $despensa));

        $this->assertDatabaseHas('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $producto->id,
            'stock' => 3,
        ]);

        $this->actingAs($editor)
            ->patch(route('despensas.stock.actualizar', [$despensa, $producto]), [
                'stock' => 7,
            ])
            ->assertRedirect(route('despensas.stock', $despensa));

        $this->assertDatabaseHas('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $producto->id,
            'stock' => 7,
        ]);

        $this->actingAs($editor)
            ->delete(route('despensas.stock.quitar', [$despensa, $producto]))
            ->assertRedirect(route('despensas.stock', $despensa));

        $this->assertDatabaseMissing('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $producto->id,
        ]);
    }

    public function test_viewer_cannot_add_or_update_or_remove_stock_in_despensa(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa restringida',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($viewer->id, ['permiso_despensa' => 'viewer']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pasta',
        ]);

        $despensa->productos()->attach($producto->id, ['stock' => 2]);

        $this->actingAs($viewer)
            ->post(route('despensas.stock.agregar', $despensa), [
                'id_producto' => $producto->id,
                'stock' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('despensas.stock.actualizar', [$despensa, $producto]), [
                'stock' => 5,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('despensas.stock.quitar', [$despensa, $producto]))
            ->assertForbidden();
    }

    public function test_stock_update_rejects_negative_values(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa validacion',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite',
        ]);
        $despensa->productos()->attach($producto->id, ['stock' => 4]);

        $this->actingAs($owner)
            ->from(route('despensas.stock', $despensa))
            ->patch(route('despensas.stock.actualizar', [$despensa, $producto]), [
                'stock' => -1,
            ])
            ->assertRedirect(route('despensas.stock', $despensa))
            ->assertSessionHasErrors('stock');

        $this->assertDatabaseHas('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $producto->id,
            'stock' => 4,
        ]);
    }

    public function test_viewer_sees_stock_page_in_read_only_mode(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa lectura',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($viewer->id, ['permiso_despensa' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->get(route('despensas.stock', $despensa));

        $response->assertOk();
        $response->assertSeeText('Modo solo lectura');
        $response->assertDontSeeText('Añadir al stock');
        $response->assertDontSeeText('Ajustar');
        $response->assertDontSeeText('Quitar');
    }

    public function test_stock_search_filters_inventory_items(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa filtro',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $arroz = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz redondo',
        ]);
        $pasta = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pasta integral',
        ]);

        $despensa->productos()->attach($arroz->id, ['stock' => 2]);
        $despensa->productos()->attach($pasta->id, ['stock' => 3]);

        $response = $this->actingAs($owner)
            ->get(route('despensas.stock', ['despensa' => $despensa->id, 'q' => 'arroz']));

        $response->assertOk();
        $response->assertSeeText('Arroz redondo');
        $response->assertDontSeeText('Pasta integral');
    }
}
