<?php

namespace Tests\Feature;

use App\Models\Despensa;
use App\Models\Lista;
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

    public function test_owner_can_add_new_editors_when_updating_a_despensa(): void
    {
        $owner = User::factory()->create();
        $nuevoEditor = User::factory()->create([
            'nombre_usuario' => 'editor_despensa',
        ]);
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa colaborativa',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $this->actingAs($owner)
            ->put(route('despensas.update', $despensa), [
                'nombre_despensa' => 'Despensa colaborativa',
                'usuarios_editores' => [$nuevoEditor->nombre_usuario],
            ])
            ->assertRedirect(route('despensas.index'));

        $this->assertDatabaseHas('tienen', [
            'id_usuario' => $nuevoEditor->id,
            'id_despensa' => $despensa->id,
            'permiso_despensa' => 'editor',
        ]);
    }

    public function test_non_admin_owner_can_load_despensa_edit_modal_data(): void
    {
        $owner = User::factory()->create([
            'rol' => 'cliente',
        ]);
        $editorActual = User::factory()->create([
            'nombre_usuario' => 'editor_actual_despensa',
        ]);
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa compartida',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($editorActual->id, ['permiso_despensa' => 'editor']);

        $response = $this->actingAs($owner)->getJson(route('despensas.edit', $despensa));

        $response->assertOk();
        $response->assertJsonPath('puedeAsignarEditores', true);
        $response->assertJsonPath('despensa.nombre_despensa', 'Despensa compartida');
        $response->assertJsonPath('usuariosEditoresActuales.0', 'editor_actual_despensa');
    }

    public function test_direct_access_to_edit_route_redirects_back_to_despensa_index(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa modal',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $this->actingAs($owner)
            ->get(route('despensas.edit', $despensa))
            ->assertRedirect(route('despensas.index'));
    }

    public function test_owner_gets_validation_error_when_despensa_editor_username_does_not_exist(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa validacion',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $this->actingAs($owner)
            ->from(route('despensas.index'))
            ->put(route('despensas.update', $despensa), [
                'nombre_despensa' => 'Despensa validacion',
                'usuarios_editores' => ['usuario_inexistente'],
            ])
            ->assertRedirect(route('despensas.index'))
            ->assertSessionHasErrors('usuarios_editores');
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
        $response->assertSeeText('Despensa lectura');
        $response->assertSeeText('Inventario actual');
        $response->assertDontSeeText('Añadir stock manualmente');
        $response->assertDontSeeText('Ajustar');
        $response->assertDontSeeText('Quitar');
    }

    public function test_updating_stock_to_zero_removes_producto_from_despensa(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa cero',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Harina',
        ]);
        $despensa->productos()->attach($producto->id, ['stock' => 2]);

        $this->actingAs($owner)
            ->patch(route('despensas.stock.actualizar', [$despensa, $producto]), [
                'stock' => 0,
            ])
            ->assertRedirect(route('despensas.stock', $despensa));

        $this->assertDatabaseMissing('almacena', [
            'id_despensa' => $despensa->id,
            'id_producto' => $producto->id,
        ]);
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
        $response->assertSeeText('Filtrado por: arroz');
    }

    public function test_stock_page_shows_add_to_list_button_only_for_non_high_stock_and_editable_active_lists(): void
    {
        $owner = User::factory()->create();
        $editorLista = User::factory()->create();
        $viewerLista = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa reposicion',
            'fecha_creacion' => now(),
        ]);
        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);

        $listaOwner = Lista::query()->create([
            'nombre_lista' => 'Compra semanal',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $listaEditor = Lista::query()->create([
            'nombre_lista' => 'Compra compartida',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $listaComprada = Lista::query()->create([
            'nombre_lista' => 'Compra cerrada',
            'estado' => 'comprada',
            'fecha_creacion' => now(),
        ]);
        $listaViewer = Lista::query()->create([
            'nombre_lista' => 'Compra solo lectura',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $listaOwner->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $listaEditor->usuarios()->attach($owner->id, ['permiso_lista' => 'editor']);
        $listaEditor->usuarios()->attach($editorLista->id, ['permiso_lista' => 'owner']);
        $listaComprada->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $listaViewer->usuarios()->attach($owner->id, ['permiso_lista' => 'viewer']);
        $listaViewer->usuarios()->attach($viewerLista->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $leche = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche',
        ]);
        $arroz = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz',
        ]);

        $despensa->productos()->attach($leche->id, ['stock' => 1]);
        $despensa->productos()->attach($arroz->id, ['stock' => 4]);

        $response = $this->actingAs($owner)->get(route('despensas.stock', $despensa));

        $response->assertOk();
        $response->assertSeeText('Añadir a lista');
        $response->assertSee('data-stock-add-to-list-open', false);
        $response->assertSeeText('Compra semanal');
        $response->assertSeeText('Compra compartida');
        $response->assertDontSeeText('Compra cerrada');
        $response->assertDontSeeText('Compra solo lectura');
        $response->assertDontSee('aria-label="Añadir Arroz a una lista"', false);
    }

    public function test_adding_producto_to_lista_from_stock_redirects_back_to_stock(): void
    {
        $owner = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa vuelta',
            'fecha_creacion' => now(),
        ]);
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista vuelta',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Garbanzos',
        ]);

        $this->actingAs($owner)
            ->post(route('listas.productos.agregar', $lista), [
                'id_producto' => $producto->id,
                'cantidad' => 2,
                'redirect_despensa_id' => $despensa->id,
            ])
            ->assertRedirect(route('despensas.stock', $despensa));

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 2,
            'marcado' => false,
        ]);
    }

    public function test_viewer_does_not_see_despensa_management_actions_in_index(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa lectura',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($viewer->id, ['permiso_despensa' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('despensas.index'));

        $response->assertOk();
        $response->assertSeeText('Stock');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_editor_sees_only_allowed_despensa_management_actions_in_index(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $despensa = Despensa::query()->create([
            'nombre_despensa' => 'Despensa editor',
            'fecha_creacion' => now(),
        ]);

        $despensa->usuarios()->attach($owner->id, ['permiso_despensa' => 'owner']);
        $despensa->usuarios()->attach($editor->id, ['permiso_despensa' => 'editor']);

        $response = $this->actingAs($editor)->get(route('despensas.index'));

        $response->assertOk();
        $response->assertSeeText('Stock');
        $response->assertSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }
}
