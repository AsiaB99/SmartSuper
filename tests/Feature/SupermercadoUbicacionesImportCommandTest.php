<?php

namespace Tests\Feature;

use App\Models\Supermercado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SupermercadoUbicacionesImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_ubicaciones_de_supermercados_de_forma_idempotente(): void
    {
        $rutaArchivo = $this->crearArchivo('osm-supermercados.json', [
            'supermercados' => [
                [
                    'external_id' => 'node:1',
                    'osm_type' => 'node',
                    'nombre' => 'Mercadona',
                    'marca' => 'Mercadona',
                    'direccion' => 'Calle Mayor 1, Madrid',
                    'latitud' => 40.416775,
                    'longitud' => -3.703790,
                ],
            ],
        ]);

        $this->artisan('supermercados:importar-ubicaciones', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Insertados: 1')
            ->assertExitCode(0);

        $this->artisan('supermercados:importar-ubicaciones', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Insertados: 0')
            ->assertExitCode(0);

        $this->assertDatabaseCount('supermercados', 1);
        $this->assertDatabaseHas('supermercados', [
            'fuente' => 'osm',
            'external_id' => 'node:1',
            'nombre_super' => 'Mercadona',
            'activo' => true,
        ]);
        $this->assertDatabaseHas('cadenas_supermercados', [
            'nombre' => 'Mercadona',
            'nombre_normalizado' => 'mercadona',
        ]);
    }

    public function test_actualiza_reactiva_inactiva_y_descarta_registros_invalidos(): void
    {
        Supermercado::query()->create([
            'nombre_super' => 'Mercadona antiguo',
            'latitud' => 40.0,
            'longitud' => -3.0,
            'fuente' => 'osm',
            'external_id' => 'node:1',
            'activo' => false,
        ]);

        Supermercado::query()->create([
            'nombre_super' => 'Tienda ausente',
            'latitud' => 41.0,
            'longitud' => -4.0,
            'fuente' => 'osm',
            'external_id' => 'node:2',
            'activo' => true,
        ]);

        $rutaArchivo = $this->crearArchivo('osm-supermercados-actualizados.json', [
            [
                'external_id' => 'node:1',
                'nombre' => 'Mercadona actualizado',
                'marca' => 'Mercadona',
                'latitud' => 40.2,
                'longitud' => -3.2,
            ],
            [
                'external_id' => 'node:3',
                'nombre' => 'Coordenadas inválidas',
                'latitud' => 120,
                'longitud' => -3.2,
            ],
        ]);

        $this->artisan('supermercados:importar-ubicaciones', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Actualizados: 1')
            ->expectsOutputToContain('Reactivados: 1')
            ->expectsOutputToContain('Inactivados: 1')
            ->expectsOutputToContain('Descartados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('supermercados', [
            'external_id' => 'node:1',
            'nombre_super' => 'Mercadona actualizado',
            'activo' => true,
        ]);
        $this->assertDatabaseHas('supermercados', [
            'external_id' => 'node:2',
            'activo' => false,
        ]);
        $this->assertDatabaseMissing('supermercados', [
            'external_id' => 'node:3',
        ]);
    }

    public function test_dry_run_no_escribe_cambios(): void
    {
        $rutaArchivo = $this->crearArchivo('osm-supermercados-dry-run.ndjson', [
            [
                'external_id' => 'node:1',
                'nombre' => 'Mercadona',
                'marca' => 'Mercadona',
                'latitud' => 40.416775,
                'longitud' => -3.703790,
            ],
        ], ndjson: true);

        $this->artisan('supermercados:importar-ubicaciones', [
            'file' => $rutaArchivo,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Dry-run completado')
            ->expectsOutputToContain('Insertados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseCount('supermercados', 0);
        $this->assertDatabaseCount('cadenas_supermercados', 0);
    }

    public function test_import_canonicalizes_catalog_chains_like_carrefour_express(): void
    {
        $rutaArchivo = $this->crearArchivo('osm-supermercados-carrefour-express.json', [
            'supermercados' => [
                [
                    'external_id' => 'node:9',
                    'nombre' => 'Carrefour Express Centro',
                    'marca' => 'Carrefour Express',
                    'latitud' => 40.41,
                    'longitud' => -3.70,
                ],
            ],
        ]);

        $this->artisan('supermercados:importar-ubicaciones', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Insertados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('cadenas_supermercados', [
            'nombre' => 'Carrefour',
            'nombre_normalizado' => 'carrefour',
        ]);

        $this->assertDatabaseMissing('cadenas_supermercados', [
            'nombre_normalizado' => 'carrefour express',
        ]);
    }

    public function test_import_repairs_mojibake_in_name_and_address(): void
    {
        $rutaArchivo = $this->crearArchivo('osm-supermercados-mojibake.json', [
            'supermercados' => [
                [
                    'external_id' => 'node:77',
                    'nombre' => 'Consum AlmerÃ­a Centro',
                    'marca' => 'Consum',
                    'direccion' => 'Calle Ãngel Jover, AlmerÃ­a',
                    'latitud' => 36.834047,
                    'longitud' => -2.463713,
                ],
            ],
        ]);

        $this->artisan('supermercados:importar-ubicaciones', ['file' => $rutaArchivo])
            ->expectsOutputToContain('Insertados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('supermercados', [
            'external_id' => 'node:77',
            'nombre_super' => 'Consum Almería Centro',
            'direccion' => 'Calle Ángel Jover, Almería',
        ]);
    }

    public function test_can_normalize_existing_supermarkets_to_catalog_chains(): void
    {
        $cadenaExpressId = \App\Models\CadenaSupermercado::query()->create([
            'nombre' => 'Carrefour Express',
            'nombre_normalizado' => 'carrefour express',
        ])->id;

        Supermercado::query()->create([
            'id_cadena' => $cadenaExpressId,
            'nombre_super' => 'Carrefour Express Centro',
            'marca' => 'Carrefour Express',
            'latitud' => 40.0,
            'longitud' => -3.0,
            'fuente' => 'osm',
            'external_id' => 'node:99',
            'activo' => true,
        ]);

        $this->artisan('supermercados:normalizar-cadenas-catalogo')
            ->expectsOutputToContain('Cadenas canónicas aseguradas: 3')
            ->expectsOutputToContain('Supermercados reasignados: 1')
            ->assertExitCode(0);

        $cadenaCarrefourId = \App\Models\CadenaSupermercado::query()
            ->where('nombre_normalizado', 'carrefour')
            ->value('id');

        $this->assertDatabaseHas('supermercados', [
            'external_id' => 'node:99',
            'id_cadena' => $cadenaCarrefourId,
        ]);
    }

    private function crearArchivo(string $nombre, array $payload, bool $ndjson = false): string
    {
        $rutaDirectorio = storage_path('app/testing');
        File::ensureDirectoryExists($rutaDirectorio);

        $rutaArchivo = $rutaDirectorio.'/'.$nombre;
        $contenido = $ndjson
            ? collect($payload)->map(fn (array $item): string => json_encode($item, JSON_THROW_ON_ERROR))->implode(PHP_EOL)
            : json_encode($payload, JSON_THROW_ON_ERROR);

        File::put($rutaArchivo, $contenido);

        return $rutaArchivo;
    }
}
