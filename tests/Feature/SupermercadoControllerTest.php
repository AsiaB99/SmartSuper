<?php

namespace Tests\Feature;

use App\Models\Supermercado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        $response->assertSeeText('Necesitamos una ubicación antes de mostrar resultados');
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
        $response->assertDontSee(route('admin.supermercados.create'), false);
    }

    public function test_supermercados_index_handles_large_imported_catalog(): void
    {
        Http::fake([
            '*' => Http::response([
                ['lat' => '40.416775', 'lon' => '-3.703790'],
            ]),
        ]);
        Supermercado::factory()->count(350)->create();

        $response = $this->get(route('supermercados.index', [
            'direccion_postal' => '28001',
        ]));

        $response->assertOk();
        $response->assertViewHas('ordenarPorCercania', true);
        $response->assertViewHas('supermercadosMapa', function ($supermercadosMapa): bool {
            return $supermercadosMapa->count() <= 300;
        });
        $response->assertViewHas('supermercados', function ($supermercados): bool {
            return $supermercados->count() <= 15;
        });
    }

    public function test_supermercados_index_prompts_for_location_when_user_has_no_coordinates(): void
    {
        $user = User::factory()->create([
            'latitud' => null,
            'longitud' => null,
        ]);
        Supermercado::factory()->create();

        $response = $this->actingAs($user)->get(route('supermercados.index'));

        $response->assertOk();
        $response->assertSeeText('Necesitamos una ubicación antes de mostrar resultados');
        $response->assertViewHas('ordenarPorCercania', false);
        $response->assertViewHas('supermercados', function ($supermercados): bool {
            return $supermercados->total() === 0;
        });
    }

    public function test_supermercados_index_orders_by_distance_when_user_has_coordinates(): void
    {
        $user = User::factory()->create([
            'latitud' => 40.416775,
            'longitud' => -3.703790,
        ]);

        $lejano = Supermercado::factory()->create([
            'nombre_super' => 'Super Lejano',
            'latitud' => 41.652251,
            'longitud' => -4.724532,
        ]);
        $cercano = Supermercado::factory()->create([
            'nombre_super' => 'Super Cercano',
            'latitud' => 40.417000,
            'longitud' => -3.704000,
        ]);

        $response = $this->actingAs($user)->get(route('supermercados.index'));

        $response->assertOk();
        $response->assertDontSeeText('Necesitamos una ubicación antes de mostrar resultados');
        $response->assertViewHas('ordenarPorCercania', true);
        $response->assertViewHas('supermercados', function ($supermercados) use ($cercano, $lejano): bool {
            return $supermercados->count() === 1
                && $supermercados->first()->id === $cercano->id
                && $supermercados->first()->distancia_km !== null;
        });
    }

    public function test_supermercados_index_can_geocode_postal_address_for_guest_search(): void
    {
        Http::fake([
            '*' => Http::response([
                ['lat' => '40.416775', 'lon' => '-3.703790'],
            ]),
        ]);

        $cercano = Supermercado::factory()->create([
            'nombre_super' => 'Super Centro',
            'latitud' => 40.417000,
            'longitud' => -3.704000,
        ]);
        Supermercado::factory()->create([
            'nombre_super' => 'Super Lejano',
            'latitud' => 41.652251,
            'longitud' => -4.724532,
        ]);

        $response = $this->get(route('supermercados.index', [
            'direccion_postal' => '28001',
        ]));

        $response->assertOk();
        $response->assertViewHas('ordenarPorCercania', true);
        $response->assertViewHas('supermercados', function ($supermercados) use ($cercano): bool {
            return $supermercados->count() === 1
                && $supermercados->first()->id === $cercano->id;
        });
    }

    public function test_supermercados_index_shows_message_when_postal_address_cannot_be_geocoded(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);
        Supermercado::factory()->create();

        $response = $this->get(route('supermercados.index', [
            'direccion_postal' => 'direccion inventada',
        ]));

        $response->assertOk();
        $response->assertSeeText('No hemos podido localizar esa dirección postal');
        $response->assertViewHas('ordenarPorCercania', false);
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

        $response->assertRedirect(route('admin.index', ['tab' => 'supermercados']));
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

        $response->assertRedirect(route('admin.index', ['tab' => 'supermercados']));
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

        $response->assertRedirect(route('admin.index', ['tab' => 'supermercados']));
        $this->assertDatabaseMissing('supermercados', [
            'id' => $supermercado->id,
        ]);
    }

    public function test_admin_can_create_supermercados_with_repeated_name(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        Supermercado::factory()->create(['nombre_super' => 'Supermercado Duplicado']);

        $response = $this->actingAs($admin)
            ->post(route('admin.supermercados.store'), [
                'nombre_super' => 'Supermercado Duplicado',
                'latitud' => -34.603722,
                'longitud' => -58.381592,
            ]);

        $response->assertRedirect(route('admin.index', ['tab' => 'supermercados']));
        $this->assertDatabaseCount('supermercados', 2);
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

    public function test_legacy_admin_supermercado_pages_redirect_to_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $supermercado = Supermercado::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.supermercados.create'))
            ->assertRedirect(route('admin.index', ['tab' => 'supermercados']));

        $this->actingAs($admin)
            ->get(route('admin.supermercados.edit', $supermercado))
            ->assertRedirect(route('admin.index', ['tab' => 'supermercados']));
    }
}
