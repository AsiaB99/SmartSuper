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

    public function test_admin_can_create_own_personal_lists(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->post(route('listas.store'), [
                'nombre_lista' => 'Lista admin',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('listas.index'));

        $lista = Lista::query()->where('nombre_lista', 'Lista admin')->firstOrFail();

        $this->assertDatabaseHas('hacen', [
            'id_usuario' => $admin->id,
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

    public function test_owner_can_add_new_editors_when_updating_a_lista(): void
    {
        $owner = User::factory()->create();
        $nuevoEditor = User::factory()->create([
            'nombre_usuario' => 'editor_nuevo',
        ]);
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista colaborativa',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $this->actingAs($owner)
            ->put(route('listas.update', $lista), [
                'nombre_lista' => 'Lista colaborativa',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
                'usuarios_editores' => [$nuevoEditor->nombre_usuario],
            ])
            ->assertRedirect(route('listas.index'));

        $this->assertDatabaseHas('hacen', [
            'id_usuario' => $nuevoEditor->id,
            'id_lista' => $lista->id,
            'permiso_lista' => 'editor',
        ]);
    }

    public function test_non_admin_owner_can_load_edit_modal_data(): void
    {
        $owner = User::factory()->create([
            'rol' => 'cliente',
        ]);
        $nuevoEditor = User::factory()->create([
            'nombre_usuario' => 'editor_lista',
            'name' => 'Editor Lista',
            'email' => 'editor-lista@example.com',
        ]);
        $editorActual = User::factory()->create([
            'nombre_usuario' => 'editor_actual',
        ]);
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista compartida',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editorActual->id, ['permiso_lista' => 'editor']);

        $response = $this->actingAs($owner)->getJson(route('listas.edit', $lista));

        $response->assertOk();
        $response->assertJsonPath('puedeAsignarEditores', true);
        $response->assertJsonPath('lista.nombre_lista', 'Lista compartida');
        $response->assertJsonMissingPath('usuariosDisponibles');
        $response->assertJsonPath('usuariosEditoresActuales.0', 'editor_actual');
        $response->assertDontSeeText('editor_lista');
    }

    public function test_direct_access_to_edit_route_redirects_back_to_list_index(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista modal',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $this->actingAs($owner)
            ->get(route('listas.edit', $lista))
            ->assertRedirect(route('listas.index'));
    }

    public function test_show_page_displays_lista_participants_with_their_permissions(): void
    {
        $owner = User::factory()->create([
            'nombre_usuario' => 'owner_lista',
            'name' => 'Owner Lista',
        ]);
        $editor = User::factory()->create([
            'nombre_usuario' => 'editor_lista',
            'name' => 'Editor Lista',
        ]);
        $viewer = User::factory()->create([
            'nombre_usuario' => 'viewer_lista',
            'name' => 'Viewer Lista',
        ]);
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista compartida',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editor->id, ['permiso_lista' => 'editor']);
        $lista->usuarios()->attach($viewer->id, ['permiso_lista' => 'viewer']);

        $response = $this->actingAs($owner)->get(route('listas.show', $lista));

        $response->assertOk();
        $response->assertSeeText('Participan en esta lista:');
        $response->assertSeeText('owner_lista');
        $response->assertSeeText('editor_lista');
        $response->assertSeeText('viewer_lista');
        $response->assertSeeText('Owner');
        $response->assertSeeText('Editor');
        $response->assertSeeText('Viewer');
    }

    public function test_editor_cannot_add_new_editors_when_updating_a_lista(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $nuevoEditor = User::factory()->create([
            'nombre_usuario' => 'editor_extra',
        ]);
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista permisos',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);
        $lista->usuarios()->attach($editor->id, ['permiso_lista' => 'editor']);

        $this->actingAs($editor)
            ->put(route('listas.update', $lista), [
                'nombre_lista' => 'Lista permisos editada',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
                'usuarios_editores' => [$nuevoEditor->nombre_usuario],
            ])
            ->assertRedirect(route('listas.index'));

        $this->assertDatabaseMissing('hacen', [
            'id_usuario' => $nuevoEditor->id,
            'id_lista' => $lista->id,
            'permiso_lista' => 'editor',
        ]);
    }

    public function test_owner_gets_validation_error_when_editor_username_does_not_exist(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista validacion',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $this->actingAs($owner)
            ->from(route('listas.index'))
            ->put(route('listas.update', $lista), [
                'nombre_lista' => 'Lista validacion',
                'estado' => 'activa',
                'fecha_creacion' => now()->format('Y-m-d H:i:s'),
                'usuarios_editores' => ['usuario_inexistente'],
            ])
            ->assertRedirect(route('listas.index'))
            ->assertSessionHasErrors('usuarios_editores');
    }

    public function test_admin_cannot_delete_any_lista(): void
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
            ->assertForbidden();

        $this->assertDatabaseHas('listas', ['id' => $lista->id]);
    }

    public function test_admin_cannot_view_lists_owned_by_other_users(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista privada usuario',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);

        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $this->actingAs($admin)
            ->get(route('listas.show', $lista))
            ->assertForbidden();
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
        $response->assertSeeText('Finalizar');
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
        $response->assertSeeText('Lista lectura');
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
        $response->assertSeeText('Busca un producto para empezar');
    }

    public function test_productos_page_does_not_load_catalog_until_user_searches(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista sin catálogo inicial',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('listas.productos', $lista));

        $response->assertOk();
        $response->assertSeeText('Busca un producto para empezar');
        $response->assertDontSeeText('Leche entera');
    }

    public function test_productos_page_shows_paginated_catalog_filtered_by_search(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista catálogo',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);

        foreach (range(1, 8) as $indice) {
            Producto::query()->create([
                'id_seccion' => $seccion->id,
                'nombre_producto' => 'Arroz '.str_pad((string) $indice, 2, '0', STR_PAD_LEFT),
            ]);
        }

        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('listas.productos', ['lista' => $lista, 'q' => 'Arroz']));

        $response->assertOk();
        $response->assertSeeText('Arroz 01');
        $response->assertSeeText('Arroz 06');
        $response->assertDontSeeText('Arroz 07');
        $response->assertDontSeeText('Leche entera');
        $response->assertSee('q=Arroz&amp;page=2', false);
        $response->assertDontSeeText('Limpiar');
    }

    public function test_productos_page_returns_dynamic_catalog_for_ajax_search(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista dinámica',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);

        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche semidesnatada',
        ]);
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz redondo',
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('listas.productos', ['lista' => $lista, 'q' => 'Leche']));

        $response->assertOk()
            ->assertJsonStructure(['catalogo']);

        $this->assertStringContainsString('Leche semidesnatada', $response->json('catalogo'));
    }

    public function test_productos_page_can_add_product_via_ajax_without_losing_catalog_state(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista ajax',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);

        $response = $this->actingAs($owner)
            ->postJson(route('listas.productos.agregar', $lista), [
                'id_producto' => $producto->id,
                'cantidad' => 1,
                'q' => 'Leche',
                'page' => 1,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['status', 'catalogo', 'listaHtml', 'resumenHtml']);

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 1,
        ]);

        $this->assertStringContainsString('Leche entera', $response->json('catalogo'));
        $this->assertStringContainsString('Leche entera', $response->json('listaHtml'));
    }

    public function test_productos_page_can_update_product_quantity_via_ajax_without_reloading(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista ajax patch',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Cafe molido',
        ]);

        $lista->productos()->attach($producto->id, ['cantidad' => 2, 'marcado' => false]);

        $response = $this->actingAs($owner)
            ->patchJson(route('listas.productos.actualizar', [$lista, $producto]), [
                'cantidad' => 5,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['status', 'listaHtml', 'resumenHtml']);

        $this->assertDatabaseHas('formadas', [
            'id_lista' => $lista->id,
            'id_producto' => $producto->id,
            'cantidad' => 5,
        ]);

        $this->assertStringContainsString('Cafe molido', $response->json('listaHtml'));
    }

    public function test_productos_page_shows_canonical_product_fallbacks_and_placeholder_image(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista canónica',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Garbanzos cocidos',
            'marca' => null,
            'formato' => null,
            'imagen' => null,
        ]);

        $lista->productos()->attach($producto->id, ['cantidad' => 2, 'marcado' => false]);

        $response = $this->actingAs($owner)
            ->get(route('listas.productos', $lista));

        $response->assertOk();
        $response->assertSeeText('Marca no disponible');
        $response->assertSeeText('Formato no informado');
        $response->assertSee('img/productos/placeholder.svg', false);
    }

    public function test_product_suggestions_returns_matching_names(): void
    {
        $owner = User::factory()->create();
        $lista = Lista::query()->create([
            'nombre_lista' => 'Lista sugerencias',
            'estado' => 'activa',
            'fecha_creacion' => now(),
        ]);
        $lista->usuarios()->attach($owner->id, ['permiso_lista' => 'owner']);

        $seccion = Seccion::query()->create(['nombre_seccion' => 'Basicos']);

        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche semidesnatada',
        ]);
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
        ]);
        Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz redondo',
        ]);

        $this->actingAs($owner)
            ->getJson(route('listas.productos.sugerencias', ['lista' => $lista, 'q' => 'Leche']))
            ->assertOk()
            ->assertExactJson([
                'Leche entera',
                'Leche semidesnatada',
            ]);
    }
}
