<?php

namespace Tests\Feature;

use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_sections_from_normalized_catalog_files(): void
    {
        $directorio = storage_path('app/testing/database-seeder-secciones');
        File::deleteDirectory($directorio);
        File::ensureDirectoryExists($directorio.'/mercadona');
        File::ensureDirectoryExists($directorio.'/carrefour');

        File::put($directorio.'/mercadona/mercadona-aceite-normalizado.json', json_encode([
            'categoria' => [
                'id' => '1',
                'nombre' => 'Aceite, vinagre y sal',
            ],
            'productos' => [],
        ], JSON_THROW_ON_ERROR));

        File::put($directorio.'/carrefour/carrefour-bebe-normalizado.json', json_encode([
            'categoria' => [
                'id' => '2',
                'nombre' => 'Bebé',
            ],
            'productos' => [],
        ], JSON_THROW_ON_ERROR));

        File::put($directorio.'/carrefour/carrefour-bebe-duplicado-normalizado.json', json_encode([
            'categoria' => [
                'id' => '3',
                'nombre' => 'Bebé',
            ],
            'productos' => [],
        ], JSON_THROW_ON_ERROR));

        putenv("SECCIONES_CATALOGO_PATH={$directorio}");
        $_ENV['SECCIONES_CATALOGO_PATH'] = $directorio;
        $_SERVER['SECCIONES_CATALOGO_PATH'] = $directorio;

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertDatabaseHas('secciones', ['nombre_seccion' => 'Aceite, vinagre y sal']);
        $this->assertDatabaseHas('secciones', ['nombre_seccion' => 'Bebé']);
        $this->assertSame(2, Seccion::query()->count());
    }
}
