<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductoExterno;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use JsonException;

class ImportarProductosExternosService
{
    public function __construct(
        private readonly MapeoProductosExternosService $mapeoService,
    ) {
    }

    /**
     * @return array{insertados:int, actualizados:int, total:int}
     */
    public function importarDesdeArchivo(string $rutaArchivo): array
    {
        $resultado = $this->importarDesdeRuta($rutaArchivo);

        return [
            'insertados' => $resultado['insertados'],
            'actualizados' => $resultado['actualizados'],
            'total' => $resultado['total'],
        ];
    }

    /**
     * @return array{
     *   archivos:int,
     *   insertados:int,
     *   actualizados:int,
     *   inactivados:int,
     *   total:int,
     *   procesados_ids:list<int>
     * }
     */
    public function importarDesdeRuta(string $ruta, ?string $fuenteForzada = null, bool $marcarNoDisponibles = false): array
    {
        $archivos = $this->resolverArchivos($ruta);
        $fechaImportacion = Carbon::now();
        $insertados = 0;
        $actualizados = 0;
        $procesadosIds = [];
        $externosVistosPorContexto = [];

        foreach ($archivos as $archivo) {
            $payload = $this->leerArchivo($archivo);
            $productos = $payload['productos'] ?? null;

            if (! is_array($productos)) {
                throw new \InvalidArgumentException("El archivo {$archivo} no contiene una lista válida de productos.");
            }

            foreach ($productos as $producto) {
                if (! is_array($producto)) {
                    continue;
                }

                $criterios = $this->buildCriterios($producto, $fuenteForzada);

                if ($criterios['external_id'] === '') {
                    continue;
                }

                $contextoKey = $this->buildContextoKey($criterios);
                $externosVistosPorContexto[$contextoKey]['criterios'] = $this->buildCriteriosContexto($criterios);
                $externosVistosPorContexto[$contextoKey]['external_ids'][] = $criterios['external_id'];

                $valores = $this->buildValores($producto, $fechaImportacion);
                $registro = ProductoExterno::query()->where($criterios)->first();

                if ($registro === null) {
                    $registro = ProductoExterno::query()->create($criterios + $valores);
                    $insertados++;
                } else {
                    $registro->fill($valores)->save();
                    $actualizados++;
                }

                $procesadosIds[] = $registro->id;
            }
        }

        $inactivados = 0;

        if ($marcarNoDisponibles) {
            foreach ($externosVistosPorContexto as $contexto) {
                $inactivados += ProductoExterno::query()
                    ->where($contexto['criterios'])
                    ->whereNotIn('external_id', array_values(array_unique($contexto['external_ids'])))
                    ->where('disponible', true)
                    ->update([
                        'disponible' => false,
                        'fecha_importacion' => $fechaImportacion,
                    ]);
            }
        }

        Log::info('productos_externos.importacion.resumen', [
            'ruta' => $ruta,
            'fuente_forzada' => $fuenteForzada,
            'archivos' => count($archivos),
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'inactivados' => $inactivados,
        ]);

        return [
            'archivos' => count($archivos),
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'inactivados' => $inactivados,
            'total' => $insertados + $actualizados,
            'procesados_ids' => array_values(array_unique($procesadosIds)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leerArchivo(string $rutaArchivo): array
    {
        if (! is_file($rutaArchivo)) {
            throw new \InvalidArgumentException("No existe el archivo: {$rutaArchivo}");
        }

        $contenido = file_get_contents($rutaArchivo);

        if ($contenido === false) {
            throw new \RuntimeException("No se pudo leer el archivo: {$rutaArchivo}");
        }

        try {
            $payload = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('El archivo no contiene un JSON válido.', 0, $exception);
        }

        if (! is_array($payload)) {
            throw new \InvalidArgumentException('El archivo JSON no tiene el formato esperado.');
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    private function resolverArchivos(string $ruta): array
    {
        if (is_file($ruta)) {
            return [$ruta];
        }

        if (! is_dir($ruta)) {
            throw new \InvalidArgumentException("No existe la ruta: {$ruta}");
        }

        $archivos = glob(rtrim($ruta, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json');

        if ($archivos === false || $archivos === []) {
            throw new \InvalidArgumentException("No se encontraron archivos JSON en: {$ruta}");
        }

        sort($archivos);

        return array_values($archivos);
    }

    /**
     * @param  array<string, mixed>  $producto
     * @return array{fuente:string,external_id:string,codigo_postal:?string,warehouse_id:?string}
     */
    private function buildCriterios(array $producto, ?string $fuenteForzada = null): array
    {
        return [
            'fuente' => $fuenteForzada !== null && $fuenteForzada !== ''
                ? $fuenteForzada
                : (string) ($producto['fuente'] ?? 'mercadona'),
            'external_id' => (string) ($producto['external_id'] ?? ''),
            'codigo_postal' => $this->nullableString($producto['codigo_postal'] ?? null),
            'warehouse_id' => $this->nullableString($producto['warehouse_id'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $producto
     * @return array<string, mixed>
     */
    private function buildValores(array $producto, Carbon $fechaImportacion): array
    {
        return [
            'nombre' => $this->nullableString($producto['nombre'] ?? null),
            'marca' => $this->nullableString($producto['marca'] ?? null),
            'formato' => $this->nullableString($producto['formato'] ?? null),
            'precio' => $producto['precio'] ?? null,
            'precio_anterior' => $producto['precio_anterior'] ?? null,
            'precio_unidad' => $producto['precio_unidad'] ?? null,
            'unidad_ref' => $this->nullableString($producto['unidad_ref'] ?? null),
            'tamano' => $this->nullableString($producto['tamano'] ?? null),
            'imagen' => $this->nullableString($producto['imagen'] ?? null),
            'url_producto' => $this->nullableString($producto['url_producto'] ?? null),
            'disponible' => (bool) ($producto['disponible'] ?? true),
            'categoria_id' => $this->nullableString($producto['categoria_id'] ?? null),
            'categoria_nombre' => $this->nullableString($producto['categoria_nombre'] ?? null),
            'payload' => $producto,
            'fecha_importacion' => $fechaImportacion,
        ];
    }

    /**
     * @param  array{fuente:string,external_id:string,codigo_postal:?string,warehouse_id:?string}  $criterios
     */
    private function buildContextoKey(array $criterios): string
    {
        return implode('|', [
            $criterios['fuente'],
            $criterios['codigo_postal'] ?? '',
            $criterios['warehouse_id'] ?? '',
        ]);
    }

    /**
     * @param  array{fuente:string,external_id:string,codigo_postal:?string,warehouse_id:?string}  $criterios
     * @return array{fuente:string,codigo_postal:?string,warehouse_id:?string}
     */
    private function buildCriteriosContexto(array $criterios): array
    {
        return [
            'fuente' => $criterios['fuente'],
            'codigo_postal' => $criterios['codigo_postal'],
            'warehouse_id' => $criterios['warehouse_id'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
