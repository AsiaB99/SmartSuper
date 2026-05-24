<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoFormatoCleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_limpia_nombre_duplicado_del_formato_en_productos_existentes(): void
    {
        $seccion = Seccion::factory()->create();

        $productoContaminado = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Gel limpiador hidratante',
            'marca' => 'Deliplus',
            'formato' => 'Gel limpiador hidratante, 250 mililitros',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $productoSano = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Leche entera',
            'marca' => 'Puleva',
            'formato' => 'Brick 1 l',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $this->artisan('productos:limpiar-formatos')
            ->expectsOutputToContain('Limpieza completada. Revisados: 2.')
            ->expectsOutputToContain('Productos con formato corregido: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos', [
            'id' => $productoContaminado->id,
            'formato' => '250 mililitros',
        ]);

        $this->assertDatabaseHas('productos', [
            'id' => $productoSano->id,
            'formato' => 'Brick 1 l',
        ]);
    }

    public function test_dry_run_no_persiste_cambios(): void
    {
        $seccion = Seccion::factory()->create();
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Gel limpiador hidratante',
            'marca' => 'Deliplus',
            'formato' => 'Gel limpiador hidratante, 250 mililitros',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $this->artisan('productos:limpiar-formatos', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-run completado. Revisados: 1.')
            ->expectsOutputToContain('Productos con formato corregido: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'formato' => 'Gel limpiador hidratante, 250 mililitros',
        ]);
    }
}
