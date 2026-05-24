<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CadenaSupermercado;
use App\Models\Supermercado;
use App\Support\TextEncoding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class ImportarUbicacionesSupermercadosService
{
    private const CADENAS_CATALOGO = [
        'mercadona' => ['mercadona'],
        'consum' => ['consum', 'charter consum', 'charter'],
        'carrefour' => ['carrefour', 'carrefour express', 'carrefour market', 'supeco'],
    ];

    /**
     * @return array{procesados:int,insertados:int,actualizados:int,reactivados:int,inactivados:int,descartados:int}
     */
    public function importarDesdeArchivo(string $rutaArchivo, string $fuente = 'osm', bool $dryRun = false): array
    {
        $supermercados = $this->leerArchivo($rutaArchivo);
        $ahora = Carbon::now();
        $stats = [
            'procesados' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'reactivados' => 0,
            'inactivados' => 0,
            'descartados' => 0,
        ];
        $externalIdsVistos = [];

        DB::transaction(function () use ($supermercados, $fuente, $dryRun, $ahora, &$stats, &$externalIdsVistos): void {
            foreach ($supermercados as $supermercado) {
                if (! is_array($supermercado)) {
                    $stats['descartados']++;
                    continue;
                }

                $normalizado = $this->normalizarSupermercado($supermercado, $fuente, $ahora, $dryRun);

                if ($normalizado === null) {
                    $stats['descartados']++;
                    continue;
                }

                $stats['procesados']++;
                $externalIdsVistos[] = $normalizado['external_id'];

                $registro = Supermercado::query()
                    ->where('fuente', $fuente)
                    ->where('external_id', $normalizado['external_id'])
                    ->first();

                if ($registro === null) {
                    $stats['insertados']++;

                    if (! $dryRun) {
                        Supermercado::query()->create($normalizado);
                    }

                    continue;
                }

                if (! (bool) $registro->activo) {
                    $stats['reactivados']++;
                }

                $hayCambios = $this->hayCambios($registro, $normalizado);

                if ($hayCambios) {
                    $stats['actualizados']++;
                }

                if (! $dryRun) {
                    $registro->fill($normalizado)->save();
                }
            }

            $queryAusentes = Supermercado::query()
                ->where('fuente', $fuente)
                ->where('activo', true);

            if ($externalIdsVistos !== []) {
                $queryAusentes->whereNotIn('external_id', array_unique($externalIdsVistos));
            }

            $idsAusentes = $queryAusentes->pluck('id');
            $stats['inactivados'] = $idsAusentes->count();

            if (! $dryRun && $idsAusentes->isNotEmpty()) {
                Supermercado::query()
                    ->whereIn('id', $idsAusentes)
                    ->update(['activo' => false]);
            }
        });

        return $stats;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leerArchivo(string $rutaArchivo): array
    {
        if (! is_file($rutaArchivo)) {
            throw new InvalidArgumentException("No existe el archivo: {$rutaArchivo}");
        }

        $contenido = file_get_contents($rutaArchivo);

        if ($contenido === false) {
            throw new RuntimeException("No se pudo leer el archivo: {$rutaArchivo}");
        }

        $contenido = trim($contenido);

        if ($contenido === '') {
            return [];
        }

        try {
            $payload = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->leerNdjson($contenido);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('El archivo JSON no tiene el formato esperado.');
        }

        if (isset($payload['supermercados'])) {
            $supermercados = $payload['supermercados'];
        } elseif (array_key_exists('external_id', $payload) || array_key_exists('id', $payload)) {
            $supermercados = [$payload];
        } else {
            $supermercados = $payload;
        }

        if (! is_array($supermercados)) {
            throw new InvalidArgumentException('El archivo no contiene una lista válida de supermercados.');
        }

        return array_values($supermercados);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leerNdjson(string $contenido): array
    {
        $registros = [];

        foreach (preg_split('/\R/u', $contenido) ?: [] as $linea) {
            $linea = trim($linea);

            if ($linea === '') {
                continue;
            }

            try {
                $registro = json_decode($linea, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException('El archivo no contiene JSON ni NDJSON válido.', 0, $exception);
            }

            if (is_array($registro)) {
                $registros[] = $registro;
            }
        }

        return $registros;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizarSupermercado(array $supermercado, string $fuente, Carbon $ahora, bool $dryRun): ?array
    {
        $externalId = $this->nullableString($supermercado['external_id'] ?? $supermercado['id'] ?? null);
        $latitud = $this->coordenada($supermercado['latitud'] ?? $supermercado['lat'] ?? null, -90, 90);
        $longitud = $this->coordenada($supermercado['longitud'] ?? $supermercado['lon'] ?? $supermercado['lng'] ?? null, -180, 180);

        if ($externalId === null || $latitud === null || $longitud === null) {
            return null;
        }

        $nombre = $this->nullableString($supermercado['nombre_super'] ?? $supermercado['nombre'] ?? $supermercado['name'] ?? null)
            ?? 'Supermercado '.$externalId;
        $marca = $this->nullableString($supermercado['marca'] ?? $supermercado['brand'] ?? null);
        $operador = $this->nullableString($supermercado['operador'] ?? $supermercado['operator'] ?? null);
        $cadena = $this->resolverCadena($marca ?? $operador, $dryRun);

        return [
            'id_cadena' => $cadena?->id,
            'nombre_super' => $nombre,
            'direccion' => $this->direccion($supermercado),
            'latitud' => $latitud,
            'longitud' => $longitud,
            'fuente' => $fuente,
            'external_id' => $externalId,
            'osm_type' => $this->nullableString($supermercado['osm_type'] ?? $supermercado['type'] ?? null),
            'marca' => $marca,
            'operador' => $operador,
            'activo' => true,
            'ultima_vista_en' => $ahora,
        ];
    }

    private function resolverCadena(?string $nombre, bool $dryRun): ?CadenaSupermercado
    {
        if ($nombre === null) {
            return null;
        }

        [$nombreCanonico, $normalizado] = $this->resolverIdentidadCadena($nombre);

        if ($dryRun) {
            return CadenaSupermercado::query()
                ->where('nombre_normalizado', $normalizado)
                ->first();
        }

        return CadenaSupermercado::query()->firstOrCreate(
            ['nombre_normalizado' => $normalizado],
            ['nombre' => $nombreCanonico]
        );
    }

    /**
     * @return array{reasignados:int,canonicas:int}
     */
    public function normalizarCadenasCatalogo(bool $dryRun = false): array
    {
        $reasignados = 0;
        $canonicas = 0;

        DB::transaction(function () use ($dryRun, &$reasignados, &$canonicas): void {
            $mapaCanonicas = [];

            foreach (array_keys(self::CADENAS_CATALOGO) as $normalizado) {
                $nombre = $this->nombrePresentableCadena($normalizado);

                if ($dryRun) {
                    $cadena = CadenaSupermercado::query()
                        ->where('nombre_normalizado', $normalizado)
                        ->first();

                    if ($cadena !== null) {
                        $mapaCanonicas[$normalizado] = $cadena->id;
                        $canonicas++;
                    }

                    continue;
                }

                $cadena = CadenaSupermercado::query()->firstOrCreate(
                    ['nombre_normalizado' => $normalizado],
                    ['nombre' => $nombre]
                );

                $mapaCanonicas[$normalizado] = $cadena->id;
                $canonicas++;
            }

            foreach (Supermercado::query()->select(['id', 'id_cadena', 'nombre_super', 'marca', 'operador'])->cursor() as $supermercado) {
                $normalizado = $this->resolverCadenaCatalogoDesdeSupermercado($supermercado->marca, $supermercado->operador, $supermercado->nombre_super);

                if ($normalizado === null) {
                    continue;
                }

                $idCadenaCanonica = $mapaCanonicas[$normalizado] ?? null;

                if ($idCadenaCanonica === null || (int) $supermercado->id_cadena === (int) $idCadenaCanonica) {
                    continue;
                }

                $reasignados++;

                if (! $dryRun) {
                    Supermercado::query()
                        ->whereKey($supermercado->id)
                        ->update(['id_cadena' => $idCadenaCanonica]);
                }
            }
        });

        return [
            'reasignados' => $reasignados,
            'canonicas' => $canonicas,
        ];
    }

    private function normalizarNombre(string $nombre): string
    {
        return preg_replace('/\s+/', ' ', trim(Str::lower(Str::ascii($nombre)))) ?: Str::lower($nombre);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolverIdentidadCadena(string $nombre): array
    {
        $normalizado = $this->normalizarNombre($nombre);
        $canonico = $this->resolverCadenaCatalogoDesdeTexto($normalizado);

        if ($canonico === null) {
            return [$nombre, $normalizado];
        }

        return [$this->nombrePresentableCadena($canonico), $canonico];
    }

    private function resolverCadenaCatalogoDesdeSupermercado(?string $marca, ?string $operador, ?string $nombreSuper): ?string
    {
        foreach ([$marca, $operador, $nombreSuper] as $texto) {
            if ($texto === null) {
                continue;
            }

            $canonico = $this->resolverCadenaCatalogoDesdeTexto($this->normalizarNombre($texto));

            if ($canonico !== null) {
                return $canonico;
            }
        }

        return null;
    }

    private function resolverCadenaCatalogoDesdeTexto(string $textoNormalizado): ?string
    {
        foreach (self::CADENAS_CATALOGO as $canonico => $aliases) {
            foreach ($aliases as $alias) {
                if (Str::contains($textoNormalizado, $alias)) {
                    return $canonico;
                }
            }
        }

        return null;
    }

    private function nombrePresentableCadena(string $normalizado): string
    {
        return match ($normalizado) {
            'mercadona' => 'Mercadona',
            'consum' => 'Consum',
            'carrefour' => 'Carrefour',
            default => Str::headline($normalizado),
        };
    }

    private function direccion(array $supermercado): ?string
    {
        $direccion = $this->nullableString($supermercado['direccion'] ?? $supermercado['address'] ?? null);

        if ($direccion !== null) {
            return $direccion;
        }

        $partes = array_filter([
            $supermercado['addr:street'] ?? null,
            $supermercado['addr:housenumber'] ?? null,
            $supermercado['addr:postcode'] ?? null,
            $supermercado['addr:city'] ?? null,
        ], static fn ($parte): bool => $parte !== null && $parte !== '');

        if ($partes === []) {
            return null;
        }

        return implode(', ', array_map('strval', $partes));
    }

    private function coordenada(mixed $value, int $min, int $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordenada = (float) $value;

        if ($coordenada < $min || $coordenada > $max) {
            return null;
        }

        return $coordenada;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        $value = TextEncoding::fixMojibake($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $normalizado
     */
    private function hayCambios(Supermercado $registro, array $normalizado): bool
    {
        foreach ($normalizado as $campo => $valor) {
            if ($campo === 'ultima_vista_en') {
                continue;
            }

            if (in_array($campo, ['latitud', 'longitud'], true)) {
                if (round((float) $registro->getAttribute($campo), 8) !== round((float) $valor, 8)) {
                    return true;
                }

                continue;
            }

            if ((string) $registro->getAttribute($campo) !== (string) $valor) {
                return true;
            }
        }

        return false;
    }
}
