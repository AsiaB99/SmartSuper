<?php

use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use App\Services\ActualizarCatalogoExternoService;
use App\Services\ImportarProductosExternosService;
use App\Services\ImportarUbicacionesSupermercadosService;
use App\Services\MapeoProductosExternosService;
use App\Support\CatalogoDemo;
use App\Support\TaxonomiaSecciones;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('productos-externos:importar-json
    {path : Archivo JSON o directorio con JSON normalizados}
    {--fuente= : Fuerza la fuente para todos los productos del lote}
    {--marcar-no-disponibles : Inactiva los productos ausentes del lote importado}
', function (
    string $path,
    ImportarProductosExternosService $service,
    MapeoProductosExternosService $mapeoService,
): void {
    $ruta = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1
        ? $path
        : base_path($path);

    $resultado = $service->importarDesdeRuta(
        $ruta,
        $this->option('fuente') !== null ? trim((string) $this->option('fuente')) : null,
        (bool) $this->option('marcar-no-disponibles'),
    );

    if ($resultado['procesados_ids'] !== []) {
        $procesados = ProductoExterno::query()->whereIn('id', $resultado['procesados_ids'])->get();

        $mapeoService->generarSugerencias($procesados);
        $pendientesAntes = ProductoExterno::query()
            ->whereIn('id', $resultado['procesados_ids'])
            ->whereIn('mapeo_estado', [ProductoExterno::ESTADO_PENDIENTE, ProductoExterno::ESTADO_SUGERIDO])
            ->count();

        $materializados = $mapeoService->materializarPendientes(
            ProductoExterno::query()->whereIn('id', $resultado['procesados_ids'])->get()
        );

        $pendientesDespues = $materializados
            ->filter(fn (ProductoExterno $productoExterno): bool => in_array($productoExterno->mapeo_estado, [
                ProductoExterno::ESTADO_PENDIENTE,
                ProductoExterno::ESTADO_SUGERIDO,
            ], true))
            ->count();

        $creadosEnCatalogo = max(0, $pendientesAntes - $pendientesDespues);
    } else {
        $creadosEnCatalogo = 0;
    }

    $this->info("Importación completada. Archivos: {$resultado['archivos']}.");
    $this->line("Insertados: {$resultado['insertados']}");
    $this->line("Actualizados: {$resultado['actualizados']}");
    $this->line("Inactivados: {$resultado['inactivados']}");
    $this->line("Materializados en catálogo: {$creadosEnCatalogo}");
})->purpose('Importa productos externos normalizados desde archivo o directorio');

Artisan::command('mercadona:importar-json {file}', function (
    string $file,
): void {
    $this->call('productos-externos:importar-json', [
        'path' => $file,
        '--fuente' => 'mercadona',
    ]);
})->purpose('Alias histórico para importar JSON normalizado de productos externos');

Artisan::command('productos-externos:regenerar-mapeo 
    {--fuente= : Filtra por fuente, por ejemplo mercadona}
    {--estado= : Filtra por estado actual de mapeo}
    {--limit=0 : Límite máximo de registros a procesar}
    {--batch=500 : Tamaño de lote para procesar sin agotar memoria}
', function (
    MapeoProductosExternosService $service,
): void {
    $fuente = trim((string) $this->option('fuente'));
    $estado = trim((string) $this->option('estado'));
    $limit = max(0, (int) $this->option('limit'));
    $batch = max(1, (int) $this->option('batch'));

    $query = ProductoExterno::query()
        ->when($fuente !== '', function ($builder) use ($fuente): void {
            $builder->where('fuente', $fuente);
        })
        ->when($estado !== '', function ($builder) use ($estado): void {
            $builder->where('mapeo_estado', $estado);
        })
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $total = (clone $query)->count();

    if ($total === 0) {
        $this->warn('No hay productos externos que cumplan los filtros indicados.');

        return;
    }

    $resumen = [
        ProductoExterno::ESTADO_PENDIENTE => 0,
        ProductoExterno::ESTADO_SUGERIDO => 0,
        ProductoExterno::ESTADO_MAPEADO => 0,
        ProductoExterno::ESTADO_DESCARTADO => 0,
    ];

    $procesados = 0;

    (clone $query)->chunkById($batch, function ($productosExternos) use ($service, &$resumen, &$procesados): void {
        $service->generarSugerencias($productosExternos);
        $materializados = $service->materializarPendientes(
            ProductoExterno::query()->whereIn('id', $productosExternos->pluck('id'))->get()
        );

        foreach ($materializados as $productoExterno) {
            $resumen[$productoExterno->mapeo_estado] = ($resumen[$productoExterno->mapeo_estado] ?? 0) + 1;
            $procesados++;
        }
    }, 'id');

    $this->info("Reprocesados: {$procesados} productos externos.");
    $this->line("Pendientes: {$resumen[ProductoExterno::ESTADO_PENDIENTE]}");
    $this->line("Sugeridos: {$resumen[ProductoExterno::ESTADO_SUGERIDO]}");
    $this->line("Mapeados: {$resumen[ProductoExterno::ESTADO_MAPEADO]}");
    $this->line("Descartados: {$resumen[ProductoExterno::ESTADO_DESCARTADO]}");
})->purpose('Regenera sugerencias de mapeo para productos externos ya importados');

Artisan::command('productos-externos:materializar-catalogo
    {--fuente= : Filtra por fuente, por ejemplo mercadona}
    {--estado=* : Estados a materializar. Por defecto procesa pendiente, sugerido y mapeado}
    {--limit=0 : Límite máximo de registros a procesar}
    {--batch=500 : Tamaño de lote para procesar sin agotar memoria}
', function (
    MapeoProductosExternosService $service,
): void {
    $fuente = trim((string) $this->option('fuente'));
    $limit = max(0, (int) $this->option('limit'));
    $batch = max(1, (int) $this->option('batch'));
    $estados = collect((array) $this->option('estado'))
        ->flatMap(fn (string $estado): array => array_filter(array_map('trim', explode(',', $estado))))
        ->filter()
        ->values();

    if ($estados->isEmpty()) {
        $estados = collect([
            ProductoExterno::ESTADO_PENDIENTE,
            ProductoExterno::ESTADO_SUGERIDO,
            ProductoExterno::ESTADO_MAPEADO,
        ]);
    }

    $query = ProductoExterno::query()
        ->when($fuente !== '', fn ($builder) => $builder->where('fuente', $fuente))
        ->whereIn('mapeo_estado', $estados->all())
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $total = (clone $query)->count();

    if ($total === 0) {
        $this->warn('No hay productos externos que cumplan los filtros indicados.');

        return;
    }

    $resumen = [
        ProductoExterno::ESTADO_PENDIENTE => 0,
        ProductoExterno::ESTADO_SUGERIDO => 0,
        ProductoExterno::ESTADO_MAPEADO => 0,
        ProductoExterno::ESTADO_DESCARTADO => 0,
    ];

    $procesados = 0;

    (clone $query)->chunkById($batch, function ($productosExternos) use ($service, &$resumen, &$procesados): void {
        $materializados = $service->materializarPendientes($productosExternos);

        foreach ($materializados as $productoExterno) {
            $resumen[$productoExterno->mapeo_estado] = ($resumen[$productoExterno->mapeo_estado] ?? 0) + 1;
            $procesados++;
        }
    }, 'id');

    $this->info("Materializados: {$procesados} productos externos.");
    $this->line("Pendientes: {$resumen[ProductoExterno::ESTADO_PENDIENTE]}");
    $this->line("Sugeridos: {$resumen[ProductoExterno::ESTADO_SUGERIDO]}");
    $this->line("Mapeados: {$resumen[ProductoExterno::ESTADO_MAPEADO]}");
    $this->line("Descartados: {$resumen[ProductoExterno::ESTADO_DESCARTADO]}");
})->purpose('Materializa en catálogo los productos externos ya importados sin recalcular matching');

Artisan::command('productos:marcar-demo-existentes', function (): void {
    $afectados = Producto::query()
        ->whereIn('nombre_producto', CatalogoDemo::nombresProducto())
        ->update(['origen_catalogo' => Producto::ORIGEN_DEMO]);

    $this->info("Productos marcados como demo: {$afectados}.");
})->purpose('Marca como demo los productos heredados del seeder antiguo');

Artisan::command('productos:limpiar-formatos
    {--dry-run : Calcula cuántos productos se limpiarían sin guardar cambios}
    {--limit=0 : Límite máximo de productos a revisar}
    {--origen= : Filtra por origen_catalogo}
', function (
    MapeoProductosExternosService $service,
): void {
    $dryRun = (bool) $this->option('dry-run');
    $limit = max(0, (int) $this->option('limit'));
    $origen = trim((string) $this->option('origen'));

    $query = Producto::query()
        ->whereNotNull('formato')
        ->where('formato', '!=', '')
        ->when($origen !== '', fn ($builder) => $builder->where('origen_catalogo', $origen))
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $revisados = 0;
    $afectados = 0;

    (clone $query)->chunkById(500, function ($productos) use ($service, $dryRun, &$revisados, &$afectados): void {
        foreach ($productos as $producto) {
            $revisados++;

            if ($dryRun) {
                $original = $producto->formato !== null ? trim((string) $producto->formato) : null;
                $nuevo = $service->resolverFormatoLimpioProducto($producto);

                if ($original !== $nuevo) {
                    $afectados++;
                }

                continue;
            }

            if ($service->limpiarFormatoProducto($producto)) {
                $afectados++;
            }
        }
    }, 'id');

    $prefijo = $dryRun ? 'Dry-run completado' : 'Limpieza completada';

    $this->info("{$prefijo}. Revisados: {$revisados}.");
    $this->line("Productos con formato corregido: {$afectados}");
})->purpose('Elimina del formato el nombre duplicado del producto cuando aparece al inicio');

Artisan::command('catalogo:actualizar
    {--fuente=* : Limita la ejecución a una o varias fuentes}
    {--solo-importar : Salta el scraping y solo importa los JSON existentes}
    {--solo-scraping : Ejecuta scraping sin importar a Laravel}
', function (
    ActualizarCatalogoExternoService $service,
): void {
    $soloImportar = (bool) $this->option('solo-importar');
    $soloScraping = (bool) $this->option('solo-scraping');

    if ($soloImportar && $soloScraping) {
        $this->error('No puedes combinar --solo-importar y --solo-scraping.');

        return;
    }

    $resultado = $service->ejecutar(
        array_values(array_filter((array) $this->option('fuente'))),
        $soloScraping,
        $soloImportar,
    );

    foreach ($resultado['fuentes'] as $fuente => $detalle) {
        $estado = $detalle['errores'] === [] ? 'OK' : 'ERROR';
        $this->info("[{$estado}] {$fuente}");
        $this->line("  Scraping: {$detalle['scraping']}");
        $this->line("  Importación: {$detalle['importacion']}");
        $this->line("  Archivos: {$detalle['archivos']}");
        $this->line("  Insertados: {$detalle['insertados']}");
        $this->line("  Actualizados: {$detalle['actualizados']}");
        $this->line("  Inactivados: {$detalle['inactivados']}");
        $this->line("  Mapeados reprocesados: {$detalle['mapeados_reprocesados']}");

        foreach ($detalle['errores'] as $error) {
            $this->line("  Error: {$error}");
        }
    }

    $resumen = $resultado['resumen'];

    $this->newLine();
    $this->info('Resumen global');
    $this->line("Fuentes OK: {$resumen['fuentes_ok']}");
    $this->line("Fuentes con error: {$resumen['fuentes_con_error']}");
    $this->line("Archivos: {$resumen['archivos']}");
    $this->line("Insertados: {$resumen['insertados']}");
    $this->line("Actualizados: {$resumen['actualizados']}");
    $this->line("Inactivados: {$resumen['inactivados']}");
    $this->line("Mapeados reprocesados: {$resumen['mapeados_reprocesados']}");
})
    ->purpose('Orquesta scraping, importación y remapeo del catálogo externo')
    ->when((bool) config('catalogo_externo.scheduler_enabled'))
    ->daily()
    ->withoutOverlapping();

Artisan::command('supermercados:importar-ubicaciones
    {file : Ruta al JSON o NDJSON normalizado}
    {--fuente=osm : Fuente de los datos importados}
    {--dry-run : Calcula el resultado sin escribir en base de datos}
', function (
    string $file,
    ImportarUbicacionesSupermercadosService $service,
): void {
    $ruta = str_starts_with($file, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $file) === 1
        ? $file
        : base_path($file);

    $resultado = $service->importarDesdeArchivo(
        $ruta,
        (string) $this->option('fuente'),
        (bool) $this->option('dry-run'),
    );

    $prefijo = $this->option('dry-run') ? 'Dry-run completado' : 'Importación completada';

    $this->info("{$prefijo}. Procesados: {$resultado['procesados']}.");
    $this->line("Insertados: {$resultado['insertados']}");
    $this->line("Actualizados: {$resultado['actualizados']}");
    $this->line("Reactivados: {$resultado['reactivados']}");
    $this->line("Inactivados: {$resultado['inactivados']}");
    $this->line("Descartados: {$resultado['descartados']}");
})->purpose('Importa ubicaciones normalizadas de supermercados de forma idempotente');

Artisan::command('supermercados:normalizar-cadenas-catalogo
    {--dry-run : Calcula la reasignación sin escribir en base de datos}
', function (
    ImportarUbicacionesSupermercadosService $service,
): void {
    $resultado = $service->normalizarCadenasCatalogo((bool) $this->option('dry-run'));
    $prefijo = $this->option('dry-run') ? 'Dry-run completado' : 'Normalización completada';

    $this->info("{$prefijo}.");
    $this->line("Cadenas canónicas aseguradas: {$resultado['canonicas']}");
    $this->line("Supermercados reasignados: {$resultado['reasignados']}");
})->purpose('Reasigna supermercados existentes a las cadenas canónicas del catálogo');

Artisan::command('secciones:consolidar
    {--max=50 : Máximo de secciones a mantener}
    {--dry-run : Calcula la consolidación sin escribir en base de datos}
', function (): void {
    $max = max(1, (int) $this->option('max'));
    $dryRun = (bool) $this->option('dry-run');

    $secciones = Seccion::query()
        ->withCount('productos')
        ->orderByDesc('productos_count')
        ->orderBy('id')
        ->get();

    if ($secciones->count() <= $max) {
        $this->info("No hay nada que consolidar. Secciones actuales: {$secciones->count()} (límite {$max}).");

        return;
    }

    $seccionesConservadas = $secciones->take($max)->values();
    $seccionesAEliminar = $secciones->slice($max)->values();

    $normalizar = static function (string $texto): string {
        $ascii = Str::ascii(Str::lower(trim($texto)));
        $ascii = preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '';

        return trim($ascii);
    };

    $conservadas = $seccionesConservadas
        ->map(function (Seccion $seccion) use ($normalizar): array {
            return [
                'id' => (int) $seccion->id,
                'nombre' => (string) $seccion->nombre_seccion,
                'normalizado' => $normalizar((string) $seccion->nombre_seccion),
            ];
        })
        ->values()
        ->all();

    $movimientos = [];

    foreach ($seccionesAEliminar as $seccionOrigen) {
        $nombreOrigen = (string) $seccionOrigen->nombre_seccion;
        $origenNormalizado = $normalizar($nombreOrigen);

        $destino = collect($conservadas)
            ->map(function (array $candidato) use ($origenNormalizado): array {
                similar_text($origenNormalizado, $candidato['normalizado'], $score);

                return [
                    'id' => $candidato['id'],
                    'nombre' => $candidato['nombre'],
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->first();

        if ($destino === null) {
            continue;
        }

        $movimientos[] = [
            'origen_id' => (int) $seccionOrigen->id,
            'origen_nombre' => $nombreOrigen,
            'destino_id' => (int) $destino['id'],
            'destino_nombre' => (string) $destino['nombre'],
            'productos' => (int) $seccionOrigen->productos_count,
        ];
    }

    $totalProductosMovidos = array_sum(array_column($movimientos, 'productos'));

    if ($dryRun) {
        $this->info('Dry-run completado.');
        $this->line('Secciones actuales: '.$secciones->count());
        $this->line('Secciones finales previstas: '.$max);
        $this->line('Secciones a eliminar: '.count($movimientos));
        $this->line('Productos a reasignar: '.$totalProductosMovidos);

        return;
    }

    DB::transaction(function () use ($movimientos): void {
        foreach ($movimientos as $movimiento) {
            Producto::query()
                ->where('id_seccion', $movimiento['origen_id'])
                ->update(['id_seccion' => $movimiento['destino_id']]);
        }

        Seccion::query()
            ->whereIn('id', array_column($movimientos, 'origen_id'))
            ->delete();
    });

    $this->info('Consolidación completada.');
    $this->line('Secciones finales: '.Seccion::query()->count());
    $this->line('Secciones eliminadas: '.count($movimientos));
    $this->line('Productos reasignados: '.$totalProductosMovidos);
})->purpose('Consolida secciones existentes hasta un máximo y reasigna sus productos');

Artisan::command('secciones:normalizar-taxonomia
    {--dry-run : Calcula la reasignación sin escribir en base de datos}
    {--limit=0 : Límite máximo de productos a revisar}
', function (): void {
    $dryRun = (bool) $this->option('dry-run');
    $limit = max(0, (int) $this->option('limit'));

    $query = Producto::query()
        ->with([
            'seccion:id,nombre_seccion',
            'productosExternos:id,producto_id,categoria_nombre',
        ])
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $cacheSecciones = [];
    $revisados = 0;
    $reasignados = 0;
    $resumen = [];

    (clone $query)->chunkById(250, function ($productos) use ($dryRun, &$cacheSecciones, &$revisados, &$reasignados, &$resumen): void {
        foreach ($productos as $producto) {
            $revisados++;

            $nombreDestino = TaxonomiaSecciones::resolverParaProducto(
                $producto->nombre_producto,
                $producto->seccion?->nombre_seccion,
                $producto->productosExternos
                    ->pluck('categoria_nombre')
                    ->filter(fn (?string $categoria): bool => $categoria !== null && trim($categoria) !== '')
                    ->all()
            );

            $nombreOrigen = $producto->seccion?->nombre_seccion ?? TaxonomiaSecciones::SECCION_OTROS;

            if ($nombreOrigen === $nombreDestino) {
                continue;
            }

            $reasignados++;
            $claveResumen = $nombreOrigen.' -> '.$nombreDestino;
            $resumen[$claveResumen] = ($resumen[$claveResumen] ?? 0) + 1;

            if ($dryRun) {
                continue;
            }

            if (! array_key_exists($nombreDestino, $cacheSecciones)) {
                $cacheSecciones[$nombreDestino] = (int) Seccion::query()->firstOrCreate([
                    'nombre_seccion' => mb_substr($nombreDestino, 0, 50),
                ])->id;
            }

            $producto->forceFill([
                'id_seccion' => $cacheSecciones[$nombreDestino],
            ])->save();
        }
    }, 'id');

    if (! $dryRun) {
        Seccion::query()
            ->doesntHave('productos')
            ->delete();
    }

    arsort($resumen);
    $prefijo = $dryRun ? 'Dry-run completado' : 'Normalización completada';

    $this->info("{$prefijo}. Productos revisados: {$revisados}.");
    $this->line("Productos reasignados: {$reasignados}");
    $this->line('Secciones finales: '.Seccion::query()->count());

    foreach (array_slice($resumen, 0, 15, true) as $movimiento => $total) {
        $this->line(" - {$movimiento}: {$total}");
    }
})->purpose('Reclasifica productos a una taxonomía canónica y elimina secciones vacías');
