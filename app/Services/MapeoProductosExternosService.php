<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoExterno;
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
            ->with('seccion')
            ->orderBy('nombre_producto')
            ->get(['id', 'id_seccion', 'nombre_producto', 'marca', 'formato', 'imagen']);

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

            $snapshot = $this->buildSnapshot($mejorCandidato, $mejorScore);

            if ($this->debeAutoMapear($mejorScore, $segundoScore)) {
                $productoExterno->forceFill([
                    'producto_id' => $mejorCandidato->id,
                    'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
                    'sugerencia_score' => $this->roundScore($mejorScore),
                    'sugerencia_snapshot' => $snapshot,
                ])->save();

                return $productoExterno->refresh();
            }

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
            $productoExterno->forceFill([
                'producto_id' => $producto->id,
                'mapeo_estado' => ProductoExterno::ESTADO_MAPEADO,
                'sugerencia_snapshot' => $this->buildSnapshot($producto, $this->calcularScore($productoExterno, $producto)),
                'sugerencia_score' => $this->roundScore($this->calcularScore($productoExterno, $producto)),
            ])->save();
        });

        return $productoExterno->refresh();
    }

    /**
     * @return Collection<int, array{producto:Producto, score:float}>
     */
    public function buscarCandidatosManuales(ProductoExterno $productoExterno, ?string $busqueda = null, int $limit = 8): Collection
    {
        $query = Producto::query()
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
            ->get(['id', 'id_seccion', 'nombre_producto', 'marca', 'formato', 'imagen'])
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
}
