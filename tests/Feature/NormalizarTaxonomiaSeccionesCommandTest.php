<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizarTaxonomiaSeccionesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reclasifica_productos_y_elimina_secciones_vacias(): void
    {
        $seccionSushi = Seccion::query()->create(['nombre_seccion' => 'Sushi del Día']);
        $seccionVino = Seccion::query()->create(['nombre_seccion' => 'Tinto otras DO']);
        $seccionLabiales = Seccion::query()->create(['nombre_seccion' => 'Labiales']);

        $aceite = Producto::query()->create([
            'id_seccion' => $seccionSushi->id,
            'nombre_producto' => 'Aceite de oliva virgen extra',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $productoAmbiguo = Producto::query()->create([
            'id_seccion' => $seccionVino->id,
            'nombre_producto' => 'Selección Chef Hacendado',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $maquillaje = Producto::query()->create([
            'id_seccion' => $seccionLabiales->id,
            'nombre_producto' => 'Pintalabios mate rojo',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        ProductoExterno::factory()->create([
            'producto_id' => $productoAmbiguo->id,
            'categoria_nombre' => 'Aceite de oliva',
        ]);

        $this->artisan('secciones:normalizar-taxonomia')
            ->expectsOutputToContain('Normalización completada. Productos revisados: 3.')
            ->expectsOutputToContain('Productos reasignados: 3')
            ->assertExitCode(0);

        $seccionAceites = Seccion::query()->where('nombre_seccion', 'Aceites, vinagres y aliños')->firstOrFail();
        $seccionMaquillaje = Seccion::query()->where('nombre_seccion', 'Maquillaje')->firstOrFail();

        $this->assertDatabaseHas('productos', [
            'id' => $aceite->id,
            'id_seccion' => $seccionAceites->id,
        ]);

        $this->assertDatabaseHas('productos', [
            'id' => $productoAmbiguo->id,
            'id_seccion' => $seccionAceites->id,
        ]);

        $this->assertDatabaseHas('productos', [
            'id' => $maquillaje->id,
            'id_seccion' => $seccionMaquillaje->id,
        ]);

        $this->assertDatabaseMissing('secciones', [
            'id' => $seccionSushi->id,
        ]);

        $this->assertDatabaseMissing('secciones', [
            'id' => $seccionVino->id,
        ]);
    }

    public function test_dry_run_no_persiste_reasignaciones(): void
    {
        $seccion = Seccion::query()->create(['nombre_seccion' => 'Sushi del Día']);
        $producto = Producto::query()->create([
            'id_seccion' => $seccion->id,
            'nombre_producto' => 'Aceite de oliva virgen extra',
            'origen_catalogo' => Producto::ORIGEN_EXTERNO,
        ]);

        $this->artisan('secciones:normalizar-taxonomia', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-run completado. Productos revisados: 1.')
            ->expectsOutputToContain('Productos reasignados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'id_seccion' => $seccion->id,
        ]);

        $this->assertDatabaseHas('secciones', [
            'id' => $seccion->id,
            'nombre_seccion' => 'Sushi del Día',
        ]);
    }
}
