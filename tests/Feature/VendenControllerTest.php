<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use App\Models\User;
use App\Models\Venden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_precios(): void
    {
        $response = $this->get(route('precios.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_can_access_precios_index_in_read_only_mode(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 10.50,
            'precio_unidad' => 10.50,
            'unidad_ref' => 'unidad',
        ]);

        $response = $this->actingAs($user)->get(route('precios.index'));

        $response->assertOk();
        $response->assertDontSee(route('admin.precios.create'), false);
        $response->assertDontSeeText('Nuevo precio');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_admin_can_view_precios_index(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 10.50,
            'precio_unidad' => 10.50,
            'unidad_ref' => 'unidad',
        ]);

        $response = $this->actingAs($admin)->get(route('precios.index'));

        $response->assertOk();
        $response->assertViewHas('precios');
        $response->assertSee(route('admin.precios.create'), false);
    }

    public function test_admin_can_create_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.precios.store'), [
                'id_producto' => $producto->id,
                'id_super' => $supermercado->id,
                'precio' => 12.99,
                'precio_unidad' => 6.50,
                'unidad_ref' => 'kg',
            ]);

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseHas('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 12.99,
            'precio_unidad' => 6.50,
            'unidad_ref' => 'kg',
        ]);
    }

    public function test_admin_can_update_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 15.00,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.precios.update', [$producto->id, $supermercado->id]), [
                'precio' => 18.49,
                'precio_unidad' => 9.25,
                'unidad_ref' => 'litro',
            ]);

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseHas('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 18.49,
            'precio_unidad' => 9.25,
            'unidad_ref' => 'litro',
        ]);
    }

    public function test_admin_can_delete_precio(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 22.00,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.precios.destroy', [$producto->id, $supermercado->id]));

        $response->assertRedirect(route('precios.index'));
        $this->assertDatabaseMissing('venden', [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
        ]);
    }

    public function test_duplicate_producto_and_supermercado_pair_is_rejected(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 13.00,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.precios.store'), [
                'id_producto' => $producto->id,
                'id_super' => $supermercado->id,
                'precio' => 15.20,
            ]);

        $response->assertSessionHasErrors('id_producto');
    }

    public function test_non_admin_user_cannot_access_admin_precio_management_routes(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $seccion = Seccion::factory()->create();
        $producto = Producto::factory()->create(['id_seccion' => $seccion->id]);
        $supermercado = Supermercado::factory()->create();
        Venden::query()->create([
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 15.00,
        ]);

        $this->actingAs($user)->get(route('admin.precios.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.precios.store'), [
            'id_producto' => $producto->id,
            'id_super' => $supermercado->id,
            'precio' => 12.99,
        ])->assertForbidden();
        $this->actingAs($user)->get(route('admin.precios.edit', [$producto->id, $supermercado->id]))->assertForbidden();
        $this->actingAs($user)->put(route('admin.precios.update', [$producto->id, $supermercado->id]), [
            'precio' => 18.49,
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.precios.destroy', [$producto->id, $supermercado->id]))->assertForbidden();
    }
}
