<?php

use App\Services\ImportarProductosExternosService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mercadona:importar-json {file}', function (
    string $file,
    ImportarProductosExternosService $service
): void {
    $ruta = str_starts_with($file, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $file) === 1
        ? $file
        : base_path($file);

    $resultado = $service->importarDesdeArchivo($ruta);

    $this->info("Importación completada. Insertados: {$resultado['insertados']}. Actualizados: {$resultado['actualizados']}.");
})->purpose('Importa a BD un JSON normalizado de Mercadona');
