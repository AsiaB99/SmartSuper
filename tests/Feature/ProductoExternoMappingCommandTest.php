<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoExternoMappingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_regenerates_mapping_for_filtered_external_products(): void
    {
        $seccion = Seccion::factory()->create();

        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Arroz vaporizado SOS',
            'marca' => 'SOS',
            'formato' => 'Paquete 1 kg',
        ]);

        $externoMapeable = ProductoExterno::factory()->create([
            'fuente' => 'mercadona',
            'nombre' => 'Arroz vaporizado SÓS',
            'marca' => 'sos',
            'formato' => 'Paquete',
            'tamano' => '1 kg',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
        ]);

        $externoFueraFiltro = ProductoExterno::factory()->create([
            'fuente' => 'carrefour',
            'nombre' => 'Arroz vaporizado SOS',
            'marca' => 'SOS',
            'formato' => 'Paquete',
            'tamano' => '1 kg',
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
        ]);

        $this->artisan('productos-externos:regenerar-mapeo', [
            '--fuente' => 'mercadona',
            '--estado' => ProductoExterno::ESTADO_PENDIENTE,
        ])
            ->expectsOutput('Reprocesados: 1 productos externos.')
            ->expectsOutput('Pendientes: 0')
            ->expectsOutput('Sugeridos: 0')
            ->expectsOutput('Mapeados: 1')
            ->expectsOutput('Descartados: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'id' => $externoMapeable->id,
            'producto_id' => $producto->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
        ]);

        $this->assertDatabaseHas('productos_externos', [
            'id' => $externoFueraFiltro->id,
            'producto_id' => null,
            'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
        ]);
    }

    public function test_reports_when_no_external_products_match_filters(): void
    {
        $this->artisan('productos-externos:regenerar-mapeo', [
            '--fuente' => 'consum',
        ])
            ->expectsOutput('No hay productos externos que cumplan los filtros indicados.')
            ->assertExitCode(0);
    }
}
