<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CadenaSupermercado;
use App\Models\Producto;
use App\Models\ProductoExterno;
use App\Models\Seccion;
use App\Support\TaxonomiaSecciones;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MapeoProductosExternosService
{
    /**
     * @param  ProductoExterno|EloquentCollection<int, ProductoExterno>  $productosExternos
     * @return Collection<int, ProductoExterno>
     */
    public function generarSugerencias(ProductoExterno|EloquentCollection $productosExternos): Collection
    {
        $items = $productosExternos instanceof ProductoExterno
            ? collect([$productosExternos])
            : $productosExternos->values();

        if ($items->isEmpty()) {
            return collect();
        }

        $catalogo = Producto::query()
            ->where('origen_catalogo', '!=', Producto::ORIGEN_DEMO)
            ->with('seccion')
            ->orderBy('nombre_producto')
            ->get(['id', 'id_seccion', 'nombre_producto', 'marca', 'formato', 'imagen', 'origen_catalogo']);

        return $items->map(function (ProductoExterno $productoExterno) use ($catalogo): ProductoExterno {
            if ($productoExterno->mapeo_estado === ProductoExterno::ESTADO_MAPEADO && $productoExterno->producto_id !== null) {
                return $productoExterno;
            }

            if ($productoExterno->mapeo_estado === ProductoExterno::ESTADO_DESCARTADO) {
                return $productoExterno;
            }

            [$mejorCandidato, $mejorScore, $segundoScore] = $this->resolverMejorCandidato($productoExterno, $catalogo);

            if ($mejorCandidato === null) {
                $productoExterno->forceFill([
                    'producto_id' => null,
                    'mapeo_estado' => ProductoExterno::ESTADO_PENDIENTE,
                    'sugerencia_score' => null,
                    'sugerencia_snapshot' => null,
                ])->save();

                return $productoExterno->refresh();
            }

            if ($this->debeAutoMapear($mejorScore, $segundoScore)) {
                DB::transaction(function () use ($productoExterno, $mejorCandidato, $mejorScore): void {
                    $this->aplicarMapeoConfirmado($productoExterno, $mejorCandidato, $mejorScore);
                });

                return $productoExterno->refresh();
            }

            $snapshot = $this->buildSnapshot($mejorCandidato, $mejorScore);

            $productoExterno->forceFill([
                'producto_id' => null,
                'mapeo_estado' => $mejorScore >= 0.6
                    ? ProductoExterno::ESTADO_SUGERIDO
                    : ProductoExterno::ESTADO_PENDIENTE,
                'sugerencia_score' => $mejorScore >= 0.6 ? $this->roundScore($mejorScore) : null,
                'sugerencia_snapshot' => $mejorScore >= 0.6 ? $snapshot : null,
            ])->save();

            return $productoExterno->refresh();
        });
    }

    public function confirmarMapeo(ProductoExterno $productoExterno, Producto $producto): ProductoExterno
    {
        DB::transaction(function () use ($productoExterno, $producto): void {
            $score = $this->calcularScore($productoExterno, $producto);

            $this->aplicarMapeoConfirmado($productoExterno, $producto, $score);
        });

        return $productoExterno->refresh();
    }

    /**
     * @return Collection<int, array{producto:Producto, score:float}>
     */
    public function buscarCandidatosManuales(ProductoExterno $productoExterno, ?string $busqueda = null, int $limit = 8): Collection
    {
        $query = Producto::query()
            ->where('origen_catalogo', '!=', Producto::ORIGEN_DEMO)
            ->with('seccion')
            ->orderBy('nombre_producto');

        $texto = trim((string) $busqueda);

        if ($texto !== '') {
            $query->where(function ($builder) use ($texto): void {
                $builder->where('nombre_producto', 'like', "%{$texto}%")
                    ->orWhere('marca', 'like', "%{$texto}%")
                    ->orWhere('formato', 'like', "%{$texto}%");
            });
        }

        return $query->limit(max($limit * 3, $limit))
            ->get(['id', 'id_seccion', 'nombre_producto', 'marca', 'formato', 'imagen', 'origen_catalogo'])
            ->map(fn (Producto $producto): array => [
                'producto' => $producto,
                'score' => $this->calcularScore($productoExterno, $producto),
            ])
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function crearYMapearProducto(ProductoExterno $productoExterno, array $atributos): ProductoExterno
    {
        return DB::transaction(function () use ($productoExterno, $atributos): ProductoExterno {
            $atributos['origen_catalogo'] = Producto::ORIGEN_EXTERNO;
            $producto = Producto::query()->create($atributos);

            return $this->confirmarMapeo($productoExterno, $producto);
        });
    }

    public function descartar(ProductoExterno $productoExterno): ProductoExterno
    {
        $productoExterno->forceFill([
            'producto_id' => null,
            'mapeo_estado' => ProductoExterno::ESTADO_DESCARTADO,
        ])->save();

        return $productoExterno->refresh();
    }

    /**
     * @param  ProductoExterno|EloquentCollection<int, ProductoExterno>  $productosExternos
     * @return Collection<int, ProductoExterno>
     */
    public function materializarPendientes(ProductoExterno|EloquentCollection $productosExternos): Collection
    {
        $items = $productosExternos instanceof ProductoExterno
            ? collect([$productosExternos])
            : $productosExternos->values();

        return $items->map(function (ProductoExterno $productoExterno): ProductoExterno {
            $productoExterno->refresh();

            if ($productoExterno->mapeo_estado === ProductoExterno::ESTADO_DESCARTADO) {
                return $productoExterno;
            }

            if ($productoExterno->mapeo_estado === ProductoExterno::ESTADO_MAPEADO && $productoExterno->producto_id !== null) {
                return $this->sincronizarPreciosCadena($productoExterno);
            }

            if ($productoExterno->mapeo_estado === ProductoExterno::ESTADO_SUGERIDO) {
                $productoSugeridoId = (int) ($productoExterno->sugerencia_snapshot['id'] ?? 0);

                if ($productoSugeridoId > 0) {
                    $producto = Producto::query()->find($productoSugeridoId);

                    if ($producto !== null) {
                        return $this->confirmarMapeo($productoExterno, $producto);
                    }
                }
            }

            return $this->crearYMapearProducto($productoExterno, $this->buildAtributosCanonicosDesdeExterno($productoExterno));
        });
    }

    public function sincronizarPreciosCadena(ProductoExterno $productoExterno): ProductoExterno
    {
        if ($productoExterno->producto_id === null || $productoExterno->precio === null) {
            return $productoExterno;
        }

        $cadena = $this->resolverCadenaDesdeFuente($productoExterno->fuente);

        DB::table('precios_cadena')->updateOrInsert(
            [
                'id_producto' => $productoExterno->producto_id,
                'id_cadena' => $cadena->id,
            ],
            [
                'precio' => $productoExterno->precio,
                'precio_unidad' => $productoExterno->precio_unidad,
                'unidad_ref' => $this->limitarLongitud($productoExterno->unidad_ref, 20),
                'fecha_actualizacion' => $productoExterno->fecha_importacion ?? now(),
            ]
        );

        return $productoExterno->refresh();
    }

    public function limpiarFormatoProducto(Producto $producto): bool
    {
        $formatoLimpio = $this->resolverFormatoLimpioProducto($producto);

        if ($formatoLimpio === $this->normalizarCampoPersistible($producto->formato)) {
            return false;
        }

        $producto->forceFill([
            'formato' => $formatoLimpio,
        ])->save();

        return true;
    }

    public function resolverFormatoLimpioProducto(Producto $producto): ?string
    {
        return $this->sanearFormatoDuplicado($producto->formato, $producto->nombre_producto);
    }

    /**
     * @param  EloquentCollection<int, Producto>  $catalogo
     * @return array{0:?Producto,1:float,2:float}
     */
    private function resolverMejorCandidato(ProductoExterno $productoExterno, EloquentCollection $catalogo): array
    {
        $scored = $catalogo->map(fn (Producto $producto): array => [
            'producto' => $producto,
            'score' => $this->calcularScore($productoExterno, $producto),
        ])->sortByDesc('score')->values();

        /** @var array{producto:Producto, score:float}|null $primero */
        $primero = $scored->get(0);
        /** @var array{producto:Producto, score:float}|null $segundo */
        $segundo = $scored->get(1);

        return [
            $primero['producto'] ?? null,
            $primero['score'] ?? 0.0,
            $segundo['score'] ?? 0.0,
        ];
    }

    private function debeAutoMapear(float $mejorScore, float $segundoScore): bool
    {
        return $mejorScore >= 0.93 && ($mejorScore - $segundoScore) >= 0.12;
    }

    private function calcularScore(ProductoExterno $productoExterno, Producto $producto): float
    {
        $nombreExterno = $this->normalizarTexto($productoExterno->nombre);
        $nombreInterno = $this->normalizarTexto($producto->nombre_producto);
        $marcaExterna = $this->normalizarTexto($productoExterno->marca);
        $marcaInterna = $this->normalizarTexto($producto->marca);
        $formatoExterno = $this->normalizarTexto(trim(collect([$productoExterno->formato, $productoExterno->tamano])->filter()->implode(' ')));
        $formatoInterno = $this->normalizarTexto($producto->formato);

        $scoreNombre = $this->scoreTexto($nombreExterno, $nombreInterno);
        $scoreMarca = $this->scoreMarca($marcaExterna, $marcaInterna, $nombreExterno, $nombreInterno);
        $scoreFormato = $this->scoreTexto($formatoExterno, $formatoInterno);

        return min(1.0, ($scoreNombre * 0.65) + ($scoreMarca * 0.25) + ($scoreFormato * 0.10));
    }

    private function scoreMarca(string $marcaExterna, string $marcaInterna, string $nombreExterno, string $nombreInterno): float
    {
        if ($marcaExterna !== '' && $marcaInterna !== '') {
            return $this->scoreTexto($marcaExterna, $marcaInterna);
        }

        if ($marcaExterna !== '' && Str::contains($nombreInterno, $marcaExterna)) {
            return 0.8;
        }

        if ($marcaInterna !== '' && Str::contains($nombreExterno, $marcaInterna)) {
            return 0.8;
        }

        return 0.0;
    }

    private function scoreTexto(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        similar_text($a, $b, $percent);

        $tokensA = collect(explode(' ', $a))->filter();
        $tokensB = collect(explode(' ', $b))->filter();

        if ($tokensA->isEmpty() || $tokensB->isEmpty()) {
            return round($percent / 100, 4);
        }

        $interseccion = $tokensA->intersect($tokensB)->count();
        $union = $tokensA->unique()->count() + $tokensB->unique()->count() - $interseccion;
        $jaccard = $union > 0 ? $interseccion / $union : 0.0;

        return max(round($percent / 100, 4), round($jaccard, 4));
    }

    /**
     * @return array{id:int,nombre_producto:string,marca:?string,formato:?string,seccion:?string,score:float}
     */
    private function buildSnapshot(Producto $producto, float $score): array
    {
        return [
            'id' => $producto->id,
            'nombre_producto' => $producto->nombre_producto,
            'marca' => $producto->marca,
            'formato' => $producto->formato,
            'seccion' => $producto->seccion?->nombre_seccion,
            'score' => $this->roundScore($score),
        ];
    }

    private function normalizarTexto(?string $texto): string
    {
        $texto = Str::of((string) $texto)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->value();

        return preg_replace('/\s+/', ' ', $texto) ?? '';
    }

    private function roundScore(float $score): float
    {
        return round($score, 4);
    }

    private function aplicarMapeoConfirmado(ProductoExterno $productoExterno, Producto $producto, float $score): void
    {
        $this->enriquecerProductoCanonico($producto, $productoExterno);
        $producto->loadMissing('seccion');

        $productoExterno->forceFill([
            'producto_id' => $producto->id,
            'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
            'sugerencia_snapshot' => $this->buildSnapshot($producto, $score),
            'sugerencia_score' => $this->roundScore($score),
        ])->save();

        $this->sincronizarPreciosCadena($productoExterno->refresh());
    }

    private function enriquecerProductoCanonico(Producto $producto, ProductoExterno $productoExterno): void
    {
        $updates = [];

        if ($producto->marca_canonica === null && $productoExterno->marca !== null && trim($productoExterno->marca) !== '') {
            $updates['marca'] = trim($productoExterno->marca);
        }

        $formatoExterno = $this->resolverFormatoCanonicoExterno($productoExterno);

        if ($producto->formato_canonico === null && $formatoExterno !== null) {
            $updates['formato'] = $formatoExterno;
        }

        if ($producto->imagen_canonica === null && $productoExterno->imagen !== null && trim($productoExterno->imagen) !== '') {
            $updates['imagen'] = trim($productoExterno->imagen);
        }

        if ($updates !== []) {
            $producto->fill($updates)->save();
        }
    }

    private function resolverFormatoCanonicoExterno(ProductoExterno $productoExterno): ?string
    {
        $formatoBase = trim((string) $productoExterno->formato);
        $tamano = trim((string) $productoExterno->tamano);

        if ($formatoBase !== '' && $tamano !== '') {
            $tamanoNormalizado = $this->normalizarTexto($tamano);
            $formatoNormalizado = $this->normalizarTexto($formatoBase);

            if ($formatoNormalizado !== '' && Str::startsWith($tamanoNormalizado, $formatoNormalizado)) {
                $formato = $tamano;
            } else {
                $formato = "{$formatoBase} {$tamano}";
            }
        } else {
            $formato = trim(collect([$productoExterno->formato, $productoExterno->tamano])
                ->filter(fn (?string $valor): bool => $valor !== null && trim($valor) !== '')
                ->implode(' '));
        }

        return $this->sanearFormatoDuplicado($formato, $productoExterno->nombre);
    }

    /**
     * @return array{id_seccion:int,nombre_producto:string,marca:?string,formato:?string,imagen:?string}
     */
    private function buildAtributosCanonicosDesdeExterno(ProductoExterno $productoExterno): array
    {
        return [
            'id_seccion' => $this->resolverSeccionId($productoExterno),
            'nombre_producto' => $this->limitarLongitud($productoExterno->nombre, 100, 'Producto externo'),
            'marca' => $this->limitarLongitud($productoExterno->marca, 50),
            'formato' => $this->limitarLongitud($this->resolverFormatoCanonicoExterno($productoExterno), 50),
            'imagen' => $this->limitarLongitud($productoExterno->imagen, 255),
        ];
    }

    private function resolverSeccionId(ProductoExterno $productoExterno): int
    {
        $nombreSeccion = $this->limitarLongitud(
            TaxonomiaSecciones::resolverParaCategoriaExterna($productoExterno->categoria_nombre),
            50,
            TaxonomiaSecciones::SECCION_OTROS
        );

        return (int) Seccion::query()->firstOrCreate([
            'nombre_seccion' => $nombreSeccion,
        ])->id;
    }

    private function resolverCadenaDesdeFuente(string $fuente): CadenaSupermercado
    {
        $nombre = match (Str::lower(trim($fuente))) {
            'mercadona' => 'Mercadona',
            'consum' => 'Consum',
            'carrefour' => 'Carrefour',
            default => Str::headline($fuente),
        };

        return CadenaSupermercado::query()->firstOrCreate(
            ['nombre_normalizado' => Str::lower(Str::ascii($nombre))],
            ['nombre' => $nombre]
        );
    }

    private function limitarLongitud(?string $valor, int $maximo, ?string $fallback = null): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return $fallback;
        }

        return mb_substr($valor, 0, $maximo);
    }

    private function sanearFormatoDuplicado(?string $formato, ?string $nombreProducto): ?string
    {
        $formatoNormalizado = $this->normalizarCampoPersistible($formato);
        $nombreNormalizado = $this->normalizarCampoPersistible($nombreProducto);

        if ($formatoNormalizado === null || $nombreNormalizado === null) {
            return $formatoNormalizado;
        }

        if ($this->normalizarTexto($formatoNormalizado) === $this->normalizarTexto($nombreNormalizado)) {
            return null;
        }

        $tokensNombre = preg_split('/[^\pL\pN]+/u', $nombreNormalizado, -1, PREG_SPLIT_NO_EMPTY);

        if ($tokensNombre === false || $tokensNombre === []) {
            return $formatoNormalizado;
        }

        $patron = '/^\s*'.implode('[\s,;:()\/-]+', array_map(
            static fn (string $token): string => preg_quote($token, '/'),
            $tokensNombre
        )).'\b[\s,;:()\/-]*/iu';

        $sinNombre = preg_replace($patron, '', $formatoNormalizado, 1);

        if (! is_string($sinNombre) || $sinNombre === $formatoNormalizado) {
            return $formatoNormalizado;
        }

        $sinNombre = trim($sinNombre, " \t\n\r\0\x0B,;:.-/");

        return $sinNombre !== '' ? $sinNombre : null;
    }

    private function normalizarCampoPersistible(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : null;
    }
}
