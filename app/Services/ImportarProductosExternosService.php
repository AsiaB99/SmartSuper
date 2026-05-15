<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductoExterno;
use Illuminate\Support\Carbon;
use JsonException;

class ImportarProductosExternosService
{
    /**
     * @return array{insertados:int, actualizados:int, total:int}
     */
    public function importarDesdeArchivo(string $rutaArchivo): array
    {
        $payload = $this->leerArchivo($rutaArchivo);
        $productos = $payload['productos'] ?? null;

        if (! is_array($productos)) {
            throw new \InvalidArgumentException('El archivo no contiene una lista válida de productos.');
        }

        $fechaImportacion = Carbon::now();
        $insertados = 0;
        $actualizados = 0;

        foreach ($productos as $producto) {
            if (! is_array($producto)) {
                continue;
            }

            $criterios = [
                'fuente' => (string) ($producto['fuente'] ?? 'mercadona'),
                'external_id' => (string) ($producto['external_id'] ?? ''),
                'codigo_postal' => $this->nullableString($producto['codigo_postal'] ?? null),
                'warehouse_id' => $this->nullableString($producto['warehouse_id'] ?? null),
            ];

            if ($criterios['external_id'] === '') {
                continue;
            }

            $valores = [
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

            $registro = ProductoExterno::query()->where($criterios)->first();

            if ($registro === null) {
                ProductoExterno::query()->create($criterios + $valores);
                $insertados++;
                continue;
            }

            $registro->fill($valores)->save();
            $actualizados++;
        }

        return [
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'total' => $insertados + $actualizados,
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

    private function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
