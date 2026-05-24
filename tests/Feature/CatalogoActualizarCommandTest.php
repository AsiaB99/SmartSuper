<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class CatalogoActualizarCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_orchestrator_continues_when_one_source_fails(): void
    {
        $baseDir = storage_path('app/testing/catalogo-actualizar');
        $mercadonaDir = $baseDir.'/mercadona';
        $consumDir = $baseDir.'/consum';
        File::ensureDirectoryExists($mercadonaDir);
        File::ensureDirectoryExists($consumDir);

        File::put($mercadonaDir.'/mercadona.json', json_encode([
            'productos' => [
                [
                    'external_id' => 'm-1',
                    'nombre' => 'Aceite Mercadona',
                    'precio' => 5.25,
                    'codigo_postal' => '04720',
                    'warehouse_id' => '4410',
                    'fuente' => 'mercadona',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        File::put($consumDir.'/consum.json', json_encode([
            'productos' => [
                [
                    'external_id' => 'c-1',
                    'nombre' => 'Leche Consum',
                    'precio' => 1.15,
                    'codigo_postal' => '46001',
                    'warehouse_id' => 'consum-web',
                    'fuente' => 'consum',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $mercadonaScript = $baseDir.'/mercadona.ps1';
        $consumScript = $baseDir.'/consum.ps1';
        File::put($mercadonaScript, '# mercadona');
        File::put($consumScript, '# consum');

        config()->set('catalogo_externo', [
            'scheduler_enabled' => true,
            'powershell_binary' => 'pwsh',
            'process_timeout' => 60,
            'sources' => [
                'mercadona' => [
                    'enabled' => true,
                    'script' => str_replace(base_path().'\\', '', $mercadonaScript),
                    'output_dir' => str_replace(base_path().'\\', '', $mercadonaDir),
                    'arguments' => [
                        'PostalCode' => '04720',
                    ],
                ],
                'consum' => [
                    'enabled' => true,
                    'script' => str_replace(base_path().'\\', '', $consumScript),
                    'output_dir' => str_replace(base_path().'\\', '', $consumDir),
                    'arguments' => [
                        'PostalCode' => '46001',
                    ],
                ],
            ],
        ]);

        Process::fake([
            '*' => function ($process) use ($mercadonaScript, $consumScript) {
                $command = implode(' ', $process->command);

                if (str_contains($command, $mercadonaScript)) {
                    return Process::result('ok', '', 0);
                }

                if (str_contains($command, $consumScript)) {
                    return Process::result('', 'fallo consum', 1);
                }

                return Process::result('', 'proceso no esperado', 1);
            },
        ]);
        Process::preventStrayProcesses();

        $this->artisan('catalogo:actualizar')
            ->expectsOutputToContain('[OK] mercadona')
            ->expectsOutputToContain('[ERROR] consum')
            ->expectsOutputToContain('Insertados: 1')
            ->expectsOutputToContain('Fuentes OK: 1')
            ->expectsOutputToContain('Fuentes con error: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'mercadona',
            'external_id' => 'm-1',
        ]);

        $this->assertDatabaseMissing('productos_externos', [
            'fuente' => 'consum',
            'external_id' => 'c-1',
        ]);

        Process::assertRanTimes(fn ($process) => str_contains(implode(' ', $process->command), '.ps1'), 2);
    }

    public function test_orchestrator_can_run_only_import_without_launching_scrapers(): void
    {
        $baseDir = storage_path('app/testing/catalogo-solo-importar');
        $mercadonaDir = $baseDir.'/mercadona';
        File::ensureDirectoryExists($mercadonaDir);

        File::put($mercadonaDir.'/mercadona.json', json_encode([
            'productos' => [
                [
                    'external_id' => 'm-2',
                    'nombre' => 'Arroz Mercadona',
                    'precio' => 1.35,
                    'codigo_postal' => '04720',
                    'warehouse_id' => '4410',
                    'fuente' => 'mercadona',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $mercadonaScript = $baseDir.'/mercadona.ps1';
        File::put($mercadonaScript, '# mercadona');

        config()->set('catalogo_externo', [
            'scheduler_enabled' => true,
            'powershell_binary' => 'pwsh',
            'process_timeout' => 60,
            'sources' => [
                'mercadona' => [
                    'enabled' => true,
                    'script' => str_replace(base_path().'\\', '', $mercadonaScript),
                    'output_dir' => str_replace(base_path().'\\', '', $mercadonaDir),
                    'arguments' => [],
                ],
            ],
        ]);

        Process::fake();
        Process::preventStrayProcesses();

        $this->artisan('catalogo:actualizar', [
            '--solo-importar' => true,
        ])
            ->expectsOutputToContain('[OK] mercadona')
            ->expectsOutputToContain('Scraping: omitido')
            ->expectsOutputToContain('Importación: ok')
            ->assertExitCode(0);

        $this->assertDatabaseHas('productos_externos', [
            'fuente' => 'mercadona',
            'external_id' => 'm-2',
        ]);

        Process::assertNothingRan();
    }

    public function test_scheduler_registers_daily_catalog_update_when_enabled(): void
    {
        config()->set('catalogo_externo.scheduler_enabled', true);

        /** @var Schedule $schedule */
        $schedule = app(Schedule::class);

        $this->assertTrue(collect($schedule->events())->contains(function ($event): bool {
            return str_contains($event->command, 'catalogo:actualizar');
        }));
    }
}
