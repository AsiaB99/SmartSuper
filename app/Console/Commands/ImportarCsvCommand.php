<?php

namespace App\Console\Commands;

use App\Services\CsvImportService;
use Illuminate\Console\Command;
use RuntimeException;

class ImportarCsvCommand extends Command
{
    protected $signature = 'datos:importar
        {tipo : secciones|productos|supermercados|precios}
        {archivo : Ruta al archivo CSV}';

    protected $description = 'Importa datos CSV para secciones, productos, supermercados o precios';

    public function __construct(private readonly CsvImportService $csvImportService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tipo = (string) $this->argument('tipo');
        $archivo = (string) $this->argument('archivo');

        try {
            $result = match ($tipo) {
                'secciones' => $this->csvImportService->importSecciones($archivo),
                'productos' => $this->csvImportService->importProductos($archivo),
                'supermercados' => $this->csvImportService->importSupermercados($archivo),
                'precios' => $this->csvImportService->importPrecios($archivo),
                default => throw new RuntimeException("Tipo no soportado: {$tipo}"),
            };
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $pairs = [];
        foreach ($result as $key => $value) {
            $pairs[] = "{$key}={$value}";
        }

        $this->info('Importación completada: '.implode(', ', $pairs));

        return self::SUCCESS;
    }
}
