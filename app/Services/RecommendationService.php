<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lista;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
    * @return array<int, array<string, mixed>>
     */
    public function recomendarSupermercados(Lista $lista, User $usuario, float $costeKm = 0.12): array
    {
        $lineasLista = DB::table('formadas')
            ->where('id_lista', $lista->id)
            ->pluck('cantidad', 'id_producto');

        if ($lineasLista->isEmpty()) {
            return [];
        }

        if ($usuario->latitud === null || $usuario->longitud === null) {
            return [];
        }

        $productosRequeridos = $lineasLista->keys()->map(static fn ($id) => (int) $id)->all();
        $cantidadPorProducto = $lineasLista->mapWithKeys(
            static fn ($cantidad, $idProducto) => [(int) $idProducto => (int) $cantidad]
        );

        $nombresProducto = DB::table('productos')
            ->whereIn('id', $productosRequeridos)
            ->pluck('nombre_producto', 'id')
            ->mapWithKeys(static fn ($nombre, $id) => [(int) $id => (string) $nombre]);

        $supermercados = DB::table('supermercados')
            ->select('id', 'nombre_super', 'latitud', 'longitud')
            ->get()
            ->keyBy('id');

        if ($supermercados->isEmpty()) {
            return [];
        }

        $precios = DB::table('venden')
            ->whereIn('id_producto', $productosRequeridos)
            ->get(['id_super', 'id_producto', 'precio']);

        return $this->construirRanking(
            $precios,
            $supermercados,
            $cantidadPorProducto,
            $nombresProducto,
            $productosRequeridos,
            (float) $usuario->latitud,
            (float) $usuario->longitud,
            $costeKm
        );
    }

    /**
      * @param array<int, int> $productosRequeridos
      * @return array<int, array<string, mixed>>
     */
    private function construirRanking(
        Collection $precios,
        Collection $supermercados,
        Collection $cantidadPorProducto,
          Collection $nombresProducto,
        array $productosRequeridos,
        float $latitudUsuario,
        float $longitudUsuario,
        float $costeKm
    ): array {
        $agrupados = $precios->groupBy('id_super');
        $ranking = [];

        foreach ($agrupados as $idSuper => $preciosSuper) {
            $productosConPrecio = $preciosSuper
                ->pluck('id_producto')
                ->map(static fn ($id) => (int) $id)
                ->all();

            if (count(array_diff($productosRequeridos, $productosConPrecio)) > 0) {
                continue;
            }

            $supermercado = $supermercados->get((int) $idSuper);

            if ($supermercado === null) {
                continue;
            }

            $totalCesta = 0.0;
            $detalleCesta = [];
            $preciosPorProducto = $preciosSuper->keyBy('id_producto');

            foreach ($productosRequeridos as $idProducto) {
                $precioItem = $preciosPorProducto->get($idProducto);

                if ($precioItem === null) {
                    continue;
                }

                $cantidad = (int) ($cantidadPorProducto->get($idProducto, 0));
                $precioUnitario = (float) $precioItem->precio;
                $subtotal = $cantidad * $precioUnitario;
                $totalCesta += $subtotal;

                $detalleCesta[] = [
                    'id_producto' => $idProducto,
                    'nombre_producto' => (string) $nombresProducto->get($idProducto, 'Producto '.$idProducto),
                    'cantidad' => $cantidad,
                    'precio_unitario' => round($precioUnitario, 2),
                    'subtotal' => round($subtotal, 2),
                ];
            }

            $distanciaKm = $this->haversineKm(
                $latitudUsuario,
                $longitudUsuario,
                (float) $supermercado->latitud,
                (float) $supermercado->longitud
            );
            $costeDistancia = $distanciaKm * $costeKm;
            $score = $totalCesta + $costeDistancia;

            $ranking[] = [
                'id_super' => (int) $supermercado->id,
                'nombre_super' => (string) $supermercado->nombre_super,
                'total_cesta' => round($totalCesta, 2),
                'distancia_km' => round($distanciaKm, 3),
                'coste_distancia' => round($costeDistancia, 2),
                'score' => round($score, 2),
                'items_cesta' => count($detalleCesta),
                'detalle_cesta' => $detalleCesta,
            ];
        }

        usort(
            $ranking,
            static fn (array $a, array $b): int => $a['score'] <=> $b['score']
        );

        return $ranking;
    }

    private function haversineKm(
        float $latitudOrigen,
        float $longitudOrigen,
        float $latitudDestino,
        float $longitudDestino
    ): float {
        $radioTierraKm = 6371.0;

        $dLat = deg2rad($latitudDestino - $latitudOrigen);
        $dLon = deg2rad($longitudDestino - $longitudOrigen);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitudOrigen))
            * cos(deg2rad($latitudDestino))
            * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierraKm * $c;
    }
}
