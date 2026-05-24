<?php

namespace Tests\Feature;

use App\Models\CadenaSupermercado;
use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_accessing_admin_panel(): void
    {
        $this->get(route('admin.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_panel_with_three_tabs(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertOk();
        $response->assertSeeText('Panel de administración');
        $response->assertSeeText('Supermercados');
        $response->assertSeeText('Productos');
        $response->assertSeeText('Usuarios');
    }

    public function test_admin_can_toggle_entire_supermarket_chain(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $cadena = CadenaSupermercado::query()->create([
            'nombre' => 'Lidl',
            'nombre_normalizado' => 'lidl',
        ]);

        $supermercadoA = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => false,
        ]);
        $supermercadoB = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.cadenas-supermercados.toggle', $cadena))
            ->assertRedirect(route('admin.index', ['tab' => 'supermercados']));

        $this->assertDatabaseHas('supermercados', [
            'id' => $supermercadoA->id,
            'activo' => true,
        ]);
        $this->assertDatabaseHas('supermercados', [
            'id' => $supermercadoB->id,
            'activo' => true,
        ]);
    }

    public function test_admin_can_deactivate_entire_chain_when_it_has_any_active_store(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $cadena = CadenaSupermercado::query()->create([
            'nombre' => 'Carrefour',
            'nombre_normalizado' => 'carrefour',
        ]);

        $supermercadoActivo = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => true,
        ]);
        $supermercadoInactivo = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.cadenas-supermercados.toggle', $cadena), [
                'chain_action' => 'deactivate',
            ])
            ->assertRedirect(route('admin.index', ['tab' => 'supermercados']));

        $this->assertDatabaseHas('supermercados', [
            'id' => $supermercadoActivo->id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('supermercados', [
            'id' => $supermercadoInactivo->id,
            'activo' => false,
        ]);
    }

    public function test_admin_can_toggle_single_supermarket_without_affecting_others(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $cadena = CadenaSupermercado::query()->create([
            'nombre' => 'Dia',
            'nombre_normalizado' => 'dia',
        ]);

        $objetivo = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => true,
        ]);
        $otro = Supermercado::factory()->create([
            'id_cadena' => $cadena->id,
            'activo' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.supermercados.toggle', $objetivo))
            ->assertRedirect(route('admin.index', ['tab' => 'supermercados']));

        $this->assertDatabaseHas('supermercados', [
            'id' => $objetivo->id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('supermercados', [
            'id' => $otro->id,
            'activo' => true,
        ]);
    }

    public function test_admin_can_create_client_user_from_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Usuario Manual',
                'nombre_usuario' => 'usuario_manual',
                'email' => 'manual@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertRedirect(route('admin.index', ['tab' => 'usuarios']));
        $this->assertDatabaseHas('users', [
            'email' => 'manual@example.com',
            'rol' => 'cliente',
        ]);
    }

    public function test_admin_users_are_hidden_from_admin_users_tab_listing(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'name' => 'Admin Oculto',
        ]);
        $cliente = User::factory()->create([
            'rol' => 'cliente',
            'name' => 'Cliente Visible',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', ['tab' => 'usuarios']));

        $response->assertOk();
        $response->assertDontSeeText($admin->name);
        $response->assertSeeText($cliente->name);
    }

    public function test_admin_can_delete_normal_user_from_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $user = User::factory()->create(['rol' => 'cliente']);

        $response = $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $user));

        $response->assertRedirect(route('admin.index', ['tab' => 'usuarios']));
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_self_from_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $admin));

        $response->assertRedirect(route('admin.index', ['tab' => 'usuarios']));
        $response->assertSessionHasErrors('usuarios');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_products_tab_lists_products_and_links_to_external_mapping(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche fresca',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', ['tab' => 'productos']));

        $response->assertOk();
        $response->assertSeeText($producto->nombre_producto);
        $response->assertSee(route('admin.productos-externos.index'), false);
    }

    public function test_supermarket_chains_are_paginated_in_admin_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        CadenaSupermercado::factory()->count(11)->create();

        $response = $this->actingAs($admin)->get(route('admin.index', [
            'tab' => 'supermercados',
            'cadenas_page' => 2,
        ]));

        $response->assertOk();
        $response->assertViewHas('cadenas', function ($cadenas): bool {
            return $cadenas->currentPage() === 2
                && $cadenas->perPage() === 10;
        });
    }

    public function test_supermarkets_tab_returns_partial_view_for_ajax_request(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html',
            ])
            ->get(route('admin.index', ['tab' => 'supermercados']));

        $response->assertOk();
        $response->assertSee('id="admin-supermercados-tab"', false);
        $response->assertDontSee('<html', false);
    }
}
