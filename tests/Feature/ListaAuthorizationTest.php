<?php

namespace Tests\Feature;

use App\Models\Lista;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListaAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_listas(): void
    {
        $response = $this->get(route('listas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_creating_a_lista_assigns_owner_permission_to_current_user(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('listas.store'), [
                'nombre_lista' => 'Compra semanal',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('listas.index'));

        $lista = Lista::query()->where('nombre_lista', 'Compra semanal')->firstOrFail();
        $this->assertDatabaseHas('hacen', [
            'id_usuario' => $usuario->id,
            'id_lista' => $lista->id,
            'permiso_lista' => 'owner',
        ]);
    }

    public function test_viewer_cannot_update_a_lista(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista base',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $this->actingAs($viewer)
            ->put(route('listas.update', $lista), [
                'nombre_lista' => 'Intento no permitido',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertForbidden();
    }

    public function test_editor_can_update_a_lista(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista editable',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editor->id, ['permiso_lista' => 'editor']);

        $this->actingAs($editor)
            ->put(route('listas.update', $lista), [
                'nombre_lista' => 'Lista editada',
                'estado' => 'comprada',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('listas.index'));

        $this->assertDatabaseHas('listas', [
            'id' => $lista->id,
            'nombre_lista' => 'Lista editada',
            'estado' => 'comprada',
        ]);
    }

    public function test_admin_can_delete_any_lista(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista para borrar',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $this->actingAs($admin)
            ->delete(route('listas.destroy', $lista))
            ->assertRedirect(route('listas.index'));

        $this->assertDatabaseMissing('listas', ['id' => $lista->id]);
    }

    public function test_viewer_does_not_see_lista_management_actions_in_index(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista lectura',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $response = $this->actingAs($viewer)->get(route('listas.index'));

        $response->assertOk();
        $response->assertSeeText('Recomendar super');
        $response->assertDontSeeText('Finalizar lista');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_editor_sees_only_allowed_lista_management_actions_in_index(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista editor',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editor->id, ['permiso_lista' => 'editor']);

        $response = $this->actingAs($editor)->get(route('listas.index'));

        $response->assertOk();
        $response->assertSeeText('Finalizar lista');
        $response->assertSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_editor_can_add_update_mark_and_remove_products_in_lista(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista productos',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editor->id, ['permiso_lista' => 'editor']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz',
        ]);

        $this->actingAs($editor)
            ->post(route('listas.productos.agregar', $lista), [
                'id_producto' => $producto->id,
                'cantidad' => 2,
            ])
            ->assertRedirect(route('listas.productos', $lista));

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 2,
            'marcado' => false,
        ]);

        $this->actingAs($editor)
            ->patch(route('listas.productos.actualizar', [$lista, $producto]), [
                'cantidad' => 5,
                'marcado' => 1,
            ])
            ->assertRedirect(route('listas.productos', $lista));

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 5,
            'marcado' => true,
        ]);

        $this->actingAs($editor)
            ->delete(route('listas.productos.quitar', [$lista, $producto]))
            ->assertRedirect(route('listas.productos', $lista));

        $this->assertDatabaseMissing('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
        ]);
    }

    public function test_adding_duplicate_product_in_lista_sums_quantities(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista duplicados',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Pasta',
        ]);
        $lista->productos()->attach($producto->id, ['cantidad' => 2, 'marcado' => false]);

        $this->actingAs($owner)
            ->post(route('listas.productos.agregar', $lista), [
                'id_producto' => $producto->id,
                'cantidad' => 3,
            ])
            ->assertRedirect(route('listas.productos', $lista))
            ->assertSessionHas('status', 'Producto ya existente: se ha sumado la cantidad.');

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 5,
        ]);
    }

    public function test_viewer_cannot_add_or_update_or_remove_products_in_lista(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista restringida',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite',
        ]);
        $lista->productos()->attach($producto->id, ['cantidad' => 1, 'marcado' => false]);

        $this->actingAs($viewer)
            ->post(route('listas.productos.agregar', $lista), [
                'id_producto' => $producto->id,
                'cantidad' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('listas.productos.actualizar', [$lista, $producto]), [
                'cantidad' => 2,
                'marcado' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->delete(route('listas.productos.quitar', [$lista, $producto]))
            ->assertForbidden();
    }

    public function test_viewer_sees_productos_page_in_read_only_mode(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista lectura',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $response = $this->actingAs($viewer)
            ->get(route('listas.productos', $lista));

        $response->assertOk();
        $response->assertSeeText('Modo solo lectura');
        $response->assertDontSeeText('Añadir a la lista');
        $response->assertDontSeeText('Guardar');
        $response->assertDontSeeText('Quitar');
    }

    public function test_productos_page_shows_empty_state_when_lista_has_no_products(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista vacia',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $response = $this->actingAs($owner)
            ->get(route('listas.productos', $lista));

        $response->assertOk();
        $response->assertSeeText('Sin productos');
        $response->assertSeeText('Añade productos para empezar la lista de compra.');
    }
}
