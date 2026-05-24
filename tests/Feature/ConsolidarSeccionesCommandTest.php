<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Seccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsolidarSeccionesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_consolidates_sections_to_requested_limit_and_reassigns_products(): void
    {
        for ($i = 1; $i <= 55; $i++) {
            $seccion = Seccion::query()->create([
                'nombre_seccion' => "Seccion {$i}",
            ]);

            Producto::query()->create([
                'id_seccion' => $seccion->id,
                'nombre_producto' => "Producto {$i}",
                'origen_catalogo' => Producto::ORIGEN_MANUAL,
            ]);
        }

        $this->artisan('secciones:consolidar', ['--max' => 50])
            ->expectsOutput('Consolidación completada.')
            ->assertExitCode(0);

        $this->assertSame(50, Seccion::query()->count());
        $this->assertSame(55, Producto::query()->count());

        $idsSeccionVigentes = Seccion::query()->pluck('id');

        $this->assertSame(
            0,
            Producto::query()
                ->whereNotIn('id_seccion', $idsSeccionVigentes)
                ->count()
        );
    }

    public function test_dry_run_does_not_modify_data(): void
    {
        for ($i = 1; $i <= 52; $i++) {
            $seccion = Seccion::query()->create([
                'nombre_seccion' => "Categoria {$i}",
            ]);

            Producto::query()->create([
                'id_seccion' => $seccion->id,
                'nombre_producto' => "Item {$i}",
                'origen_catalogo' => Producto::ORIGEN_MANUAL,
            ]);
        }

        $this->artisan('secciones:consolidar', ['--max' => 50, '--dry-run' => true])
            ->expectsOutput('Dry-run completado.')
            ->assertExitCode(0);

        $this->assertSame(52, Seccion::query()->count());
        $this->assertSame(52, Producto::query()->count());
    }
}

