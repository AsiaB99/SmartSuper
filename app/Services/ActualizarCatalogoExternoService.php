<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductoExterno;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ActualizarCatalogoExternoService
{
    public function __construct(
        private readonly ImportarProductosExternosService $importarService,
        private readonly MapeoProductosExternosService $mapeoService,
    ) {
    }

    /**
     * @param  list<string>|null  $fuentesSolicitadas
     * @return array{fuentes:array<string, array<string, mixed>>, resumen:array<string, int>}
     */
    public function ejecutar(?array $fuentesSolicitadas = null, bool $soloScraping = false, bool $soloImportar = false): array
    {
        $fuentes = $this->resolverFuentes($fuentesSolicitadas);
        $resultadoFuentes = [];
        $resumen = [
            'fuentes_ok' => 0,
            'fuentes_con_error' => 0,
            'archivos' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'inactivados' => 0,
            'mapeados_reprocesados' => 0,
        ];

        foreach ($fuentes as $fuente => $configuracion) {
            $inicio = Carbon::now();
            $detalle = [
                'fuente' => $fuente,
                'scraping' => 'omitido',
                'importacion' => 'omitida',
                'archivos' => 0,
                'insertados' => 0,
                'actualizados' => 0,
                'inactivados' => 0,
                'mapeados_reprocesados' => 0,
                'errores' => [],
            ];

            Log::info('catalogo_externo.actualizacion.inicio', [
                'fuente' => $fuente,
                'solo_scraping' => $soloScraping,
                'solo_importar' => $soloImportar,
            ]);

            try {
                if (! $soloImportar) {
                    $this->ejecutarScraping($fuente, $configuracion);
                    $detalle['scraping'] = 'ok';
                }

                if (! $soloScraping) {
                    $importacion = $this->importarService->importarDesdeRuta(
                        base_path((string) $configuracion['output_dir']),
                        $fuente,
                        true,
                    );

                    $detalle['importacion'] = 'ok';
                    $detalle['archivos'] = $importacion['archivos'];
                    $detalle['insertados'] = $importacion['insertados'];
                    $detalle['actualizados'] = $importacion['actualizados'];
                    $detalle['inactivados'] = $importacion['inactivados'];

                    if ($importacion['procesados_ids'] !== []) {
                        $procesados = ProductoExterno::query()
                            ->whereIn('id', $importacion['procesados_ids'])
                            ->get();

                        $reprocesados = $this->mapeoService->generarSugerencias($procesados);
                        $pendientesAntes = ProductoExterno::query()
                            ->whereIn('id', $importacion['procesados_ids'])
                            ->whereIn('mapeo_estado', [
                                ProductoExterno::ESTADO_PENDIENTE,
                                ProductoExterno::ESTADO_SUGERIDO,
                            ])
                            ->count();

                        $materializados = $this->mapeoService->materializarPendientes(
                            ProductoExterno::query()
                                ->whereIn('id', $importacion['procesados_ids'])
                                ->get()
                        );

                        $pendientesDespues = $materializados
                            ->filter(fn (ProductoExterno $productoExterno): bool => in_array($productoExterno->mapeo_estado, [
                                ProductoExterno::ESTADO_PENDIENTE,
                                ProductoExterno::ESTADO_SUGERIDO,
                            ], true))
                            ->count();

                        $detalle['mapeados_reprocesados'] = max(0, $pendientesAntes - $pendientesDespues);
                    }
                }
            } catch (\Throwable $exception) {
                $detalle['errores'][] = $exception->getMessage();

                Log::error('catalogo_externo.actualizacion.error', [
                    'fuente' => $fuente,
                    'error' => $exception->getMessage(),
                ]);
            }

            $detalle['duracion_ms'] = $inicio->diffInMilliseconds(Carbon::now());
            $resultadoFuentes[$fuente] = $detalle;

            if ($detalle['errores'] === []) {
                $resumen['fuentes_ok']++;
            } else {
                $resumen['fuentes_con_error']++;
            }

            $resumen['archivos'] += $detalle['archivos'];
            $resumen['insertados'] += $detalle['insertados'];
            $resumen['actualizados'] += $detalle['actualizados'];
            $resumen['inactivados'] += $detalle['inactivados'];
            $resumen['mapeados_reprocesados'] += $detalle['mapeados_reprocesados'];

            Log::info('catalogo_externo.actualizacion.fin', [
                'fuente' => $fuente,
                'scraping' => $detalle['scraping'],
                'importacion' => $detalle['importacion'],
                'archivos' => $detalle['archivos'],
                'insertados' => $detalle['insertados'],
                'actualizados' => $detalle['actualizados'],
                'inactivados' => $detalle['inactivados'],
                'mapeados_reprocesados' => $detalle['mapeados_reprocesados'],
                'errores' => $detalle['errores'],
            ]);
        }

        return [
            'fuentes' => $resultadoFuentes,
            'resumen' => $resumen,
        ];
    }

    /**
     * @param  list<string>|null  $fuentesSolicitadas
     * @return array<string, array<string, mixed>>
     */
    private function resolverFuentes(?array $fuentesSolicitadas): array
    {
        /** @var array<string, array<string, mixed>> $fuentesConfiguradas */
        $fuentesConfiguradas = config('catalogo_externo.sources', []);

        $fuentes = collect($fuentesConfiguradas)
            ->filter(fn (array $configuracion): bool => (bool) ($configuracion['enabled'] ?? false));

        if ($fuentesSolicitadas === null || $fuentesSolicitadas === []) {
            return $fuentes->all();
        }

        $solicitadas = collect($fuentesSolicitadas)
            ->flatMap(fn (string $fuente): array => array_filter(array_map('trim', explode(',', $fuente))))
            ->filter()
            ->unique()
            ->values();

        $filtradas = $fuentes->only($solicitadas->all());

        if ($filtradas->isEmpty()) {
            throw new \InvalidArgumentException('No hay fuentes activas que coincidan con el filtro indicado.');
        }

        return $filtradas->all();
    }

    /**
     * @param  array<string, mixed>  $configuracion
     */
    private function ejecutarScraping(string $fuente, array $configuracion): void
    {
        $script = base_path((string) ($configuracion['script'] ?? ''));

        if (! is_file($script)) {
            throw new \RuntimeException("No existe el script configurado para {$fuente}: {$script}");
        }

        $comando = [
            (string) config('catalogo_externo.powershell_binary'),
            '-NoProfile',
            '-ExecutionPolicy',
            'Bypass',
            '-File',
            $script,
            ...$this->buildArgumentos($configuracion['arguments'] ?? []),
        ];

        $resultado = $this->nuevoProceso()->run($comando);

        if (! $resultado->successful()) {
            throw new \RuntimeException(
                trim($resultado->errorOutput()) !== ''
                    ? trim($resultado->errorOutput())
                    : "Falló el scraping de {$fuente}."
            );
        }
    }

    /**
     * @param  array<string, mixed>  $argumentos
     * @return list<string>
     */
    private function buildArgumentos(array $argumentos): array
    {
        $resultado = [];

        foreach ($argumentos as $nombre => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            $resultado[] = '-'.$nombre;
            $resultado[] = (string) $valor;
        }

        return $resultado;
    }

    private function nuevoProceso(): PendingProcess
    {
        return Process::path(base_path())
            ->timeout((int) config('catalogo_externo.process_timeout', 3600));
    }
}
