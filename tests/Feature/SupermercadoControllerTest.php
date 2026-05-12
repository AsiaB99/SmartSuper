<?php

namespace Tests\Feature;

use App\Models\Supermercado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupermercadoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_supermercados_index_in_read_only_mode(): void
    {
        Supermercado::factory()->count(2)->create();

        $response = $this->get(route('supermercados.index'));

        $response->assertOk();
        $response->assertSeeText('Supermercados');
        $response->assertDontSee(route('admin.supermercados.create'), false);
        $response->assertDontSeeText('Nuevo supermercado');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_non_admin_user_can_access_supermercados_index_in_read_only_mode(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        Supermercado::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('supermercados.index'));

        $response->assertOk();
        $response->assertDontSee(route('admin.supermercados.create'), false);
        $response->assertDontSeeText('Nuevo supermercado');
        $response->assertDontSeeText('Editar');
        $response->assertDontSeeText('Eliminar');
    }

    public function test_admin_can_view_supermercados_index(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        Supermercado::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('supermercados.index'));

        $response->assertOk();
        $response->assertViewHas('supermercados');
        $response->assertSee(route('admin.supermercados.create'), false);
    }

    public function test_admin_can_create_supermercado(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.supermercados.store'), [
                'nombre_super' => 'Supermercado Test',
                'latitud' => -34.603722,
                'longitud' => -58.381592,
            ]);

        $response->assertRedirect(route('supermercados.index'));
        $this->assertDatabaseHas('supermercados', [
            'nombre_super' => 'Supermercado Test',
        ]);
    }

    public function test_admin_can_update_supermercado(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $supermercado = Supermercado::factory()->create();

        $response = $this->actingAs($admin)
            ->put(route('admin.supermercados.update', $supermercado), [
                'nombre_super' => 'Supermercado Actualizado',
                'latitud' => -34.603722,
                'longitud' => -58.381592,
            ]);

        $response->assertRedirect(route('supermercados.index'));
        $this->assertDatabaseHas('supermercados', [
            'id' => $supermercado->id,
            'nombre_super' => 'Supermercado Actualizado',
        ]);
    }

    public function test_admin_can_delete_supermercado(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $supermercado = Supermercado::factory()->create();

        $response = $this->actingAs($admin)
            ->delete(route('admin.supermercados.destroy', $supermercado));

        $response->assertRedirect(route('supermercados.index'));
        $this->assertDatabaseMissing('supermercados', [
            'id' => $supermercado->id,
        ]);
    }

    public function test_supermercado_nombre_must_be_unique(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        Supermercado::factory()->create(['nombre_super' => 'Supermercado Duplicado']);

        $response = $this->actingAs($admin)
            ->post(route('admin.supermercados.store'), [
                'nombre_super' => 'Supermercado Duplicado',
                'latitud' => -34.603722,
                'longitud' => -58.381592,
            ]);

        $response->assertSessionHasErrors('nombre_super');
    }

    public function test_non_admin_user_cannot_access_admin_supermercado_management_routes(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);
        $supermercado = Supermercado::factory()->create();

        $this->actingAs($user)->get(route('admin.supermercados.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.supermercados.store'), [
            'nombre_super' => 'Supermercado restringido',
            'latitud' => -34.603722,
            'longitud' => -58.381592,
        ])->assertForbidden();
        $this->actingAs($user)->get(route('admin.supermercados.edit', $supermercado))->assertForbidden();
        $this->actingAs($user)->put(route('admin.supermercados.update', $supermercado), [
            'nombre_super' => 'Supermercado restringido',
            'latitud' => -34.603722,
            'longitud' => -58.381592,
        ])->assertForbidden();
        $this->actingAs($user)->delete(route('admin.supermercados.destroy', $supermercado))->assertForbidden();
    }
}
