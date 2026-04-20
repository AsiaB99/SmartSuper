<?php

namespace Tests\Feature;

use App\Models\Lista;
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
}
