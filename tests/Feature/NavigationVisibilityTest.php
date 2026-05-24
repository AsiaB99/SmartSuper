<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_does_not_see_productos_navigation_link(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);

        $response = $this->actingAs($user)->get(route('listas.index'));

        $response->assertOk();
        $response->assertSeeText('Mi Lista');
        $response->assertSeeText('Mi Despensa');
        $response->assertSeeText('Supermercados');
        $response->assertDontSeeText('Productos');
        $response->assertSeeText('Comparador');
        $response->assertSeeText('Perfil');
    }

    public function test_admin_user_sees_productos_navigation_link(): void
    {
        $user = User::factory()->create(['rol' => 'admin']);

        $response = $this->actingAs($user)->get(route('listas.index'));

        $response->assertOk();
        $response->assertSeeText('Administración');
        $response->assertSeeText('Mi Lista');
        $response->assertDontSeeText('Mi Despensa');
    }
}
