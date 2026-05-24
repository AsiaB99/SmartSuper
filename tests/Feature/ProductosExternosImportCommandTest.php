<?php

namespace Tests\Feature;

use App\Models\ProductoExterno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductosExternosImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_a_directory_and_marks_missing_products_as_unavailable(): void
    {
        ProductoExterno::factory()->create([
            'fuente' => 'carrefour',
            'external_id' => 'keep-1',
            'codigo_postal' => '28232',
            'warehouse_id' => '005290',
            'disponible' => true,
        ]);

        ProductoExterno::factory()->create([
            'fuente' => 'carrefour',
            'external_id' => 'missing-1',
            'codigo_postal' => '28232',
            'warehouse_id' => '005290',
            'disponible' => true,
        ]);

        $directorio = storage_path('app/testing/import-carrefour');
        File::ensureDirectoryExists($directorio);

        File::put($directorio.'/carrefour-a.json', json_encode([
            'productos' => [
                [
                    'external_id' => 'keep-1',
                    'nombre' => 'Producto existente actualizado',
                    'precio' => 2.45,
                    'codigo_postal' => '28232',
                    'warehouse_id' => '005290',
                    'fuente' => 'carrefour',
                ],
                [
                    'external_id' => 'new-1',
                    'nombre' => 'Producto nuevo',
                    'precio' => 1.99,
                    'codigo_postal' => '28232',
                    'warehouse_id' => '005290',
                    'fuente' => 'carrefour',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('productos-externos:importar-json', [
            'path' => $directorio,
            '--fuente' => 'carrefour',
            '--marcar-no-disponibles' => true,
        ])
            ->expectsOutputToContain('Archivos: 1')
            ->expectsOutputToContain('Insertados: 1')
            ->expectsOutputToContain('Actualizados: 1')
            ->expectsOutputToContain('Inactivados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'carrefour',
            'external_id' => 'keep-1',
            'nombre' => 'Producto existente actualizado',
            'disponible' => true,
        ]);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'carrefour',
            'external_id' => 'new-1',
            'disponible' => true,
        ]);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'carrefour',
            'external_id' => 'missing-1',
            'disponible' => false,
        ]);
    }
}
