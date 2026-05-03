<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Seccion;
use App\Models\Supermercado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class CsvImportService
{
    public function importSecciones(string $path): array
    {
        $result = ['processed' => 0, 'imported' => 0, 'skipped' => 0];
        $rows = $this->readRows($path);

        DB::transaction(function () use ($rows, &$result): void {
            foreach ($rows as $row) {
                $result['processed']++;
                $nombre = trim((string) ($row['nombre_seccion'] ?? ''));
                if ($nombre === '') {
                    $result['skipped']++;
                    continue;
                }

                $created = Seccion::query()->firstOrCreate(['nombre_seccion' => $nombre])->wasRecentlyCreated;
                $created ? $result['imported']++ : $result['skipped']++;
            }
        });

        return $result;
    }

    public function importSupermercados(string $path): array
    {
        $result = ['processed' => 0, 'imported' => 0, 'skipped' => 0];
        $rows = $this->readRows($path);

        DB::transaction(function () use ($rows, &$result): void {
            foreach ($rows as $row) {
                $result['processed']++;
                $nombre = trim((string) ($row['nombre_super'] ?? ''));
                if ($nombre === '') {
                    $result['skipped']++;
                    continue;
                }

                $attributes = ['nombre_super' => $nombre];
                $values = [
                    'direccion' => $this->nullableString($row['direccion'] ?? null),
                    'latitud' => $this->nullableFloat($row['latitud'] ?? null) ?? 0.0,
                    'longitud' => $this->nullableFloat($row['longitud'] ?? null) ?? 0.0,
                ];

                $supermercado = Supermercado::query()->firstOrNew($attributes);
                $wasNew = ! $supermercado->exists;
                $supermercado->fill($values)->save();
                $wasNew ? $result['imported']++ : $result['skipped']++;
            }
        });

        return $result;
    }

    public function importProductos(string $path): array
    {
        $result = ['processed' => 0, 'imported' => 0, 'updated' => 0, 'skipped' => 0];
        $rows = $this->readRows($path);

        DB::transaction(function () use ($rows, &$result): void {
            foreach ($rows as $row) {
                $result['processed']++;
                $nombre = trim((string) ($row['nombre_producto'] ?? ''));
                $seccionNombre = trim((string) ($row['seccion'] ?? ''));

                if ($nombre === '' || $seccionNombre === '') {
                    $result['skipped']++;
                    continue;
                }

                $seccion = Seccion::query()->firstOrCreate(['nombre_seccion' => $seccionNombre]);
                $codigoBarras = $this->nullableString($row['codigo_barras'] ?? null);

                $producto = $codigoBarras
                    ? Producto::query()->firstOrNew(['codigo_barras' => $codigoBarras])
                    : Producto::query()->firstOrNew([
                        'id_seccion' => $seccion->id,
                        'nombre_producto' => $nombre,
                    ]);

                $wasNew = ! $producto->exists;
                $producto->fill([
                    'id_seccion' => $seccion->id,
                    'codigo_barras' => $codigoBarras,
                    'nombre_producto' => $nombre,
                    'marca' => $this->nullableString($row['marca'] ?? null),
                    'formato' => $this->nullableString($row['formato'] ?? null),
                    'cantidad_envase' => $this->nullableFloat($row['cantidad_envase'] ?? null),
                    'unidad_envase' => $this->nullableString($row['unidad_envase'] ?? null),
                    'imagen' => $this->nullableString($row['imagen'] ?? null),
                    'fuente_datos' => $this->nullableString($row['fuente_datos'] ?? null),
                ])->save();

                if ($wasNew) {
                    $result['imported']++;
                    continue;
                }

                $producto->wasChanged() ? $result['updated']++ : $result['skipped']++;
            }
        });

        return $result;
    }

    public function importPrecios(string $path): array
    {
        $result = [
            'processed' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing_producto' => 0,
            'missing_supermercado' => 0,
        ];
        $rows = $this->readRows($path);

        DB::transaction(function () use ($rows, &$result): void {
            foreach ($rows as $row) {
                $result['processed']++;

                $codigoBarras = trim((string) ($row['codigo_barras'] ?? ''));
                $nombreSuper = trim((string) ($row['nombre_super'] ?? ''));
                $precio = $this->nullableFloat($row['precio'] ?? null);

                if ($codigoBarras === '' || $nombreSuper === '' || $precio === null) {
                    $result['skipped']++;
                    continue;
                }

                $producto = Producto::query()->where('codigo_barras', $codigoBarras)->first();
                if (! $producto) {
                    $result['missing_producto']++;
                    $result['skipped']++;
                    continue;
                }

                $supermercado = Supermercado::query()->where('nombre_super', $nombreSuper)->first();
                if (! $supermercado) {
                    $result['missing_supermercado']++;
                    $result['skipped']++;
                    continue;
                }

                $payload = [
                    'precio' => $precio,
                    'precio_unidad' => $this->nullableFloat($row['precio_unidad'] ?? null),
                    'unidad_ref' => $this->nullableString($row['unidad_ref'] ?? null),
                    'moneda' => strtoupper($this->nullableString($row['moneda'] ?? null) ?? 'EUR'),
                    'fuente_precio' => $this->nullableString($row['fuente_precio'] ?? null),
                    'url_origen' => $this->nullableString($row['url_origen'] ?? null),
                    'fecha_precio' => $this->normalizeDate($row['fecha_precio'] ?? null),
                ];

                $existing = DB::table('venden')
                    ->where('id_producto', $producto->id)
                    ->where('id_super', $supermercado->id)
                    ->first();

                if (! $existing) {
                    DB::table('venden')->insert([
                        'id_producto' => $producto->id,
                        'id_super' => $supermercado->id,
                        ...$payload,
                    ]);
                    $result['imported']++;
                    continue;
                }

                $hasChanges = (float) $existing->precio !== (float) $payload['precio']
                    || (float) ($existing->precio_unidad ?? 0) !== (float) ($payload['precio_unidad'] ?? 0)
                    || (string) ($existing->unidad_ref ?? '') !== (string) ($payload['unidad_ref'] ?? '')
                    || (string) ($existing->moneda ?? '') !== (string) $payload['moneda']
                    || (string) ($existing->fuente_precio ?? '') !== (string) ($payload['fuente_precio'] ?? '')
                    || (string) ($existing->url_origen ?? '') !== (string) ($payload['url_origen'] ?? '')
                    || $this->normalizeDate($existing->fecha_precio ?? null) !== $payload['fecha_precio'];

                if (! $hasChanges) {
                    $result['skipped']++;
                    continue;
                }

                DB::table('venden')
                    ->where('id_producto', $producto->id)
                    ->where('id_super', $supermercado->id)
                    ->update($payload);

                $result['updated']++;
            }
        });

        return $result;
    }

    private function readRows(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("No existe el archivo CSV: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(',');

        $headers = null;
        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    static fn ($header) => trim((string) $header),
                    $row
                );
                continue;
            }

            $values = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row);
            $rows[] = array_replace(array_fill_keys($headers, null), array_combine($headers, $values) ?: []);
        }

        return $rows;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $string = $this->nullableString($value);

        if ($string === null) {
            return null;
        }

        return Carbon::parse($string)->toDateString();
    }
}
