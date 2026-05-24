<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_productos(): void
    {
        $response = $this->get(route('productos.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_productos_index(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        Seccion::factory()->count(2)->create();
        Producto::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('productos.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_productos_index(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        Seccion::factory()->count(2)->create();
        Producto::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get(route('productos.index'));

        $response->assertRedirect(route('admin.index', ['tab' => 'productos']));
    }

    public function test_admin_can_create_producto(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.productos.store'), [
                'nombre_producto' => 'Producto Test',
                'id_seccion' => $seccion->id,
            ]);

        $response->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Producto Test',
            'id_seccion' => $seccion->id,
        ]);
    }

    public function test_admin_can_update_producto(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.productos.update', $producto), [
                'nombre_producto' => 'Producto Actualizado',
                'id_seccion' => $seccion->id,
            ]);

        $response->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre_producto' => 'Producto Actualizado',
            'id_seccion' => $seccion->id,
        ]);
    }

    public function test_admin_can_delete_producto(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $producto = Producto::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.productos.destroy', $producto));

        $response->assertRedirect(route('admin.index', ['tab' => 'productos']));
        $this->assertDatabaseMissing('productos', [
            'id' => $producto->id,
        ]);
    }

    public function test_producto_nombre_must_be_unique(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        Producto::factory()->create(['nombre_producto' => 'Producto Duplicado', 'id_seccion' => $seccion->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.productos.store'), [
                'nombre_producto' => 'Producto Duplicado',
                'id_seccion' => $seccion->id,
            ]);

        $response->assertSessionHasErrors('nombre_producto');
    }

    public function test_seccion_must_exist_when_creating_producto(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.productos.store'), [
                'nombre_producto' => 'Producto Test',
                'id_seccion' => 9999,
            ]);

        $response->assertSessionHasErrors('id_seccion');
    }

    public function test_non_admin_user_cannot_access_admin_producto_management_routes(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create();

        $this->actingAs($user)->get(route('admin.productos.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.productos.store'), [
            'nombre_producto' => 'Producto restringido',
            'id_seccion' => $seccion->id,
        ])->assertForbidden();
        $this->actingAs($user)->get(route('admin.productos.edit', $producto))->assertForbidden();
        $this->actingAs($user)->put(route('admin.productos.update', $producto), [
            'nombre_producto' => 'Producto restringido',
            'id_seccion' => $seccion->id,
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.productos.destroy', $producto))->assertForbidden();
    }

    public function test_legacy_admin_producto_pages_redirect_to_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $producto = Producto::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.productos.create'))
            ->assertRedirect(route('admin.index', ['tab' => 'productos']));

        $this->actingAs($admin)
            ->get(route('admin.productos.edit', $producto))
            ->assertRedirect(route('admin.index', ['tab' => 'productos']));
    }
}
