<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_catalog_navigation_links(): void
    {
        $user = User::factory()->create(['rol' => 'cliente']);

        $response = $this->actingAs($user)->get(route('listas.index'));

        $response->assertOk();
        $response->assertSeeText('Listas');
        $response->assertSeeText('Despensas');
        $response->assertSeeText('Supermercados');
        $response->assertSeeText('Productos');
        $response->assertSeeText('Precios');
        $response->assertSeeText('Perfil');
    }
}
