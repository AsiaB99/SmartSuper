<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lista;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    private const MAX_COMBINACIONES_EXACTAS = 18;

    private const MAX_TIENDAS_DIRECTAS_POR_PRODUCTO = 3;

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
            ->select('id', 'id_cadena', 'nombre_super', 'latitud', 'longitud')
            ->where('activo', true)
            ->get()
            ->keyBy('id');

        if ($supermercados->isEmpty()) {
            return [];
        }

        $precios = DB::table('venden')
            ->whereIn('id_producto', $productosRequeridos)
            ->get(['id_super', 'id_producto', 'precio']);

        $preciosCadena = DB::table('precios_cadena')
            ->whereIn('id_producto', $productosRequeridos)
            ->get(['id_cadena', 'id_producto', 'precio']);

        $candidatos = $this->construirCandidatos(
            $precios,
            $preciosCadena,
            $supermercados,
            $productosRequeridos,
            (float) $usuario->latitud,
            (float) $usuario->longitud,
            $costeKm
        );

        if ($candidatos === []) {
            return [];
        }

        return $this->construirRanking(
            $candidatos,
            $cantidadPorProducto,
            $nombresProducto,
            $productosRequeridos
        );
    }

    /**
     * @param array<int, int> $productosRequeridos
     * @return array<int, array<string, mixed>>
     */
    private function construirCandidatos(
        Collection $precios,
        Collection $preciosCadena,
        Collection $supermercados,
        array $productosRequeridos,
        float $latitudUsuario,
        float $longitudUsuario,
        float $costeKm
    ): array {
        $preciosPorSuper = $precios
            ->groupBy('id_super')
            ->map(static fn (Collection $items): Collection => $items->keyBy('id_producto'));
        $preciosPorCadena = $preciosCadena
            ->groupBy('id_cadena')
            ->map(static fn (Collection $items): Collection => $items->keyBy('id_producto'));

        $distanciasPorSuper = [];

        foreach ($supermercados as $supermercado) {
            $distanciasPorSuper[(int) $supermercado->id] = $this->haversineKm(
                $latitudUsuario,
                $longitudUsuario,
                (float) $supermercado->latitud,
                (float) $supermercado->longitud
            );
        }

        $tiendaMasCercanaPorCadena = $supermercados
            ->filter(static fn (object $supermercado): bool => $supermercado->id_cadena !== null)
            ->groupBy('id_cadena')
            ->map(function (Collection $tiendas) use ($distanciasPorSuper): object {
                return $tiendas->sortBy(
                    static fn (object $tienda): float => $distanciasPorSuper[(int) $tienda->id] ?? INF
                )->first();
            });

        $idsCandidatos = [];

        foreach ($productosRequeridos as $idProducto) {
            $opcionesDirectas = $precios
                ->where('id_producto', $idProducto)
                ->filter(static fn (object $precio): bool => $supermercados->has((int) $precio->id_super));

            $idsDirectosPorPrecio = $opcionesDirectas
                ->sortBy([
                    static fn (object $precio): float => (float) $precio->precio,
                    static fn (object $precio): float => $distanciasPorSuper[(int) $precio->id_super] ?? INF,
                ])
                ->take(self::MAX_TIENDAS_DIRECTAS_POR_PRODUCTO)
                ->pluck('id_super');

            $idsDirectosPorDistancia = $opcionesDirectas
                ->sortBy([
                    static fn (object $precio): float => $distanciasPorSuper[(int) $precio->id_super] ?? INF,
                    static fn (object $precio): float => (float) $precio->precio,
                ])
                ->take(self::MAX_TIENDAS_DIRECTAS_POR_PRODUCTO)
                ->pluck('id_super');

            foreach ($idsDirectosPorPrecio->merge($idsDirectosPorDistancia)->unique() as $idSuper) {
                $idsCandidatos[] = (int) $idSuper;
            }

            foreach ($preciosCadena->where('id_producto', $idProducto) as $precioCadena) {
                $tiendaCadena = $tiendaMasCercanaPorCadena->get((int) $precioCadena->id_cadena);

                if ($tiendaCadena !== null) {
                    $idsCandidatos[] = (int) $tiendaCadena->id;
                }
            }
        }

        $candidatos = [];

        foreach (array_values(array_unique($idsCandidatos)) as $idSuper) {
            $supermercado = $supermercados->get($idSuper);

            if ($supermercado === null) {
                continue;
            }

            $preciosSuper = $preciosPorSuper->get($idSuper, collect());
            $preciosCadenaSuper = $supermercado->id_cadena === null
                ? collect()
                : $preciosPorCadena->get((int) $supermercado->id_cadena, collect());

            $preciosProducto = [];

            foreach ($productosRequeridos as $idProducto) {
                $precioItem = $preciosSuper->get($idProducto) ?? $preciosCadenaSuper->get($idProducto);

                if ($precioItem === null) {
                    continue;
                }

                $preciosProducto[$idProducto] = (float) $precioItem->precio;
            }

            if ($preciosProducto === []) {
                continue;
            }

            $distanciaKm = $distanciasPorSuper[$idSuper] ?? 0.0;

            $candidatos[] = [
                'id_super' => $idSuper,
                'nombre_super' => (string) $supermercado->nombre_super,
                'distancia_km' => round($distanciaKm, 3),
                'coste_distancia' => round($distanciaKm * $costeKm, 2),
                'precios_producto' => $preciosProducto,
            ];
        }

        return $candidatos;
    }

    /**
     * @param array<int, array<string, mixed>> $candidatos
     * @param array<int, int> $productosRequeridos
     * @return array<int, array<string, mixed>>
     */
    private function construirRanking(
        array $candidatos,
        Collection $cantidadPorProducto,
        Collection $nombresProducto,
        array $productosRequeridos
    ): array {
        $productosDisponibles = collect($candidatos)
            ->flatMap(static fn (array $candidato): array => array_keys($candidato['precios_producto']))
            ->unique()
            ->all();

        if (array_diff($productosRequeridos, $productosDisponibles) !== []) {
            return [];
        }

        if (count($candidatos) <= self::MAX_COMBINACIONES_EXACTAS) {
            return $this->construirRankingExacto($candidatos, $cantidadPorProducto, $nombresProducto, $productosRequeridos);
        }

        $mejorCombinacion = $this->construirMejorCombinacionHeuristica(
            $candidatos,
            $cantidadPorProducto,
            $nombresProducto,
            $productosRequeridos
        );

        return $mejorCombinacion === null ? [] : [$mejorCombinacion];
    }

    /**
     * @param array<int, array<string, mixed>> $candidatos
     * @param array<int, int> $productosRequeridos
     * @return array<int, array<string, mixed>>
     */
    private function construirRankingExacto(
        array $candidatos,
        Collection $cantidadPorProducto,
        Collection $nombresProducto,
        array $productosRequeridos
    ): array {
        $rankingPorToken = [];
        $totalCandidatos = count($candidatos);
        $limite = 1 << $totalCandidatos;

        for ($mascara = 1; $mascara < $limite; $mascara++) {
            $seleccionados = [];

            for ($indice = 0; $indice < $totalCandidatos; $indice++) {
                if (($mascara & (1 << $indice)) !== 0) {
                    $seleccionados[] = $candidatos[$indice];
                }
            }

            $fila = $this->construirFilaRanking($seleccionados, $cantidadPorProducto, $nombresProducto, $productosRequeridos);

            if ($fila === null) {
                continue;
            }

            $token = (string) $fila['token'];
            $actual = $rankingPorToken[$token] ?? null;

            if ($actual === null || (float) $fila['score'] < (float) $actual['score']) {
                $rankingPorToken[$token] = $fila;
            }
        }

        $ranking = array_values($rankingPorToken);

        usort($ranking, $this->comparadorRanking(...));

        return array_slice($ranking, 0, 5);
    }

    /**
     * @param array<int, array<string, mixed>> $candidatos
     * @param array<int, int> $productosRequeridos
     * @return array<string, mixed>|null
     */
    private function construirMejorCombinacionHeuristica(
        array $candidatos,
        Collection $cantidadPorProducto,
        Collection $nombresProducto,
        array $productosRequeridos
    ): ?array {
        $seleccionados = [];
        $mejorFila = null;

        while (true) {
            $filaActual = $this->construirFilaRanking($seleccionados, $cantidadPorProducto, $nombresProducto, $productosRequeridos);

            if ($filaActual !== null) {
                $mejorFila = $filaActual;
                break;
            }

            $idsYaSeleccionados = array_column($seleccionados, 'id_super');
            $mejorCandidato = null;
            $mejorCobertura = -1;
            $mejorScoreParcial = INF;

            foreach ($candidatos as $candidato) {
                if (in_array($candidato['id_super'], $idsYaSeleccionados, true)) {
                    continue;
                }

                $parcial = [...$seleccionados, $candidato];
                $cobertura = collect($parcial)
                    ->flatMap(static fn (array $tienda): array => array_keys($tienda['precios_producto']))
                    ->unique()
                    ->count();

                $filaParcial = $this->construirFilaRanking($parcial, $cantidadPorProducto, $nombresProducto, $productosRequeridos);
                $scoreParcial = $filaParcial === null ? INF : (float) $filaParcial['score'];

                if ($cobertura > $mejorCobertura || ($cobertura === $mejorCobertura && $scoreParcial < $mejorScoreParcial)) {
                    $mejorCandidato = $candidato;
                    $mejorCobertura = $cobertura;
                    $mejorScoreParcial = $scoreParcial;
                }
            }

            if ($mejorCandidato === null) {
                return null;
            }

            $seleccionados[] = $mejorCandidato;
        }

        foreach ($seleccionados as $indice => $seleccionado) {
            $sinCandidato = $seleccionados;
            unset($sinCandidato[$indice]);
            $filaSinCandidato = $this->construirFilaRanking(array_values($sinCandidato), $cantidadPorProducto, $nombresProducto, $productosRequeridos);

            if ($filaSinCandidato !== null && (float) $filaSinCandidato['score'] <= (float) $mejorFila['score']) {
                $seleccionados = array_values($sinCandidato);
                $mejorFila = $filaSinCandidato;
            }
        }

        return $mejorFila;
    }

    /**
     * @param array<int, array<string, mixed>> $seleccionados
     * @param array<int, int> $productosRequeridos
     * @return array<string, mixed>|null
     */
    private function construirFilaRanking(
        array $seleccionados,
        Collection $cantidadPorProducto,
        Collection $nombresProducto,
        array $productosRequeridos
    ): ?array {
        if ($seleccionados === []) {
            return null;
        }

        $detalleCesta = [];
        $supermercadosUsados = [];
        $resumenPorSuper = [];
        $totalCesta = 0.0;

        foreach ($productosRequeridos as $idProducto) {
            $mejorTienda = null;
            $mejorPrecio = null;

            foreach ($seleccionados as $tienda) {
                $precio = $tienda['precios_producto'][$idProducto] ?? null;

                if ($precio === null) {
                    continue;
                }

                if (
                    $mejorPrecio === null
                    || $precio < $mejorPrecio
                    || ($precio === $mejorPrecio && (float) $tienda['coste_distancia'] < (float) ($mejorTienda['coste_distancia'] ?? INF))
                ) {
                    $mejorPrecio = $precio;
                    $mejorTienda = $tienda;
                }
            }

            if ($mejorTienda === null || $mejorPrecio === null) {
                return null;
            }

            $cantidad = (int) ($cantidadPorProducto->get($idProducto, 0));
            $subtotal = $cantidad * $mejorPrecio;
            $idSuper = (int) $mejorTienda['id_super'];

            $supermercadosUsados[$idSuper] = [
                'id_super' => $idSuper,
                'nombre_super' => (string) $mejorTienda['nombre_super'],
                'distancia_km' => (float) $mejorTienda['distancia_km'],
                'coste_distancia' => (float) $mejorTienda['coste_distancia'],
            ];

            $resumenPorSuper[$idSuper] = ($resumenPorSuper[$idSuper] ?? 0) + 1;
            $totalCesta += $subtotal;

            $detalleCesta[] = [
                'id_producto' => $idProducto,
                'nombre_producto' => (string) $nombresProducto->get($idProducto, 'Producto '.$idProducto),
                'cantidad' => $cantidad,
                'precio_unitario' => round($mejorPrecio, 2),
                'subtotal' => round($subtotal, 2),
                'id_super' => $idSuper,
                'nombre_super' => (string) $mejorTienda['nombre_super'],
            ];
        }

        foreach ($supermercadosUsados as $idSuper => $supermercado) {
            $supermercadosUsados[$idSuper]['items_cesta'] = $resumenPorSuper[$idSuper] ?? 0;
        }

        uasort($supermercadosUsados, static function (array $a, array $b): int {
            return [
                -1 * (int) $a['items_cesta'],
                (float) $a['coste_distancia'],
                (string) $a['nombre_super'],
            ] <=> [
                -1 * (int) $b['items_cesta'],
                (float) $b['coste_distancia'],
                (string) $b['nombre_super'],
            ];
        });

        $supermercados = array_values($supermercadosUsados);
        $idsSuper = array_map(static fn (array $supermercado): int => (int) $supermercado['id_super'], $supermercados);
        sort($idsSuper);

        $distanciaKm = array_sum(array_column($supermercados, 'distancia_km'));
        $costeDistancia = array_sum(array_column($supermercados, 'coste_distancia'));
        $score = $totalCesta + $costeDistancia;
        $principal = $supermercados[0];

        return [
            'token' => $this->crearTokenCombinacion($idsSuper),
            'id_super' => (int) $principal['id_super'],
            'ids_super' => $idsSuper,
            'nombre_super' => count($supermercados) === 1
                ? (string) $principal['nombre_super']
                : collect($supermercados)->pluck('nombre_super')->implode(' + '),
            'total_cesta' => round($totalCesta, 2),
            'distancia_km' => round($distanciaKm, 3),
            'coste_distancia' => round($costeDistancia, 2),
            'score' => round($score, 2),
            'items_cesta' => count($detalleCesta),
            'detalle_cesta' => $detalleCesta,
            'supermercados' => $supermercados,
            'es_combinada' => count($supermercados) > 1,
        ];
    }

    private function comparadorRanking(array $a, array $b): int
    {
        return [
            (float) $a['score'],
            count($a['ids_super']),
            (string) $a['nombre_super'],
        ] <=> [
            (float) $b['score'],
            count($b['ids_super']),
            (string) $b['nombre_super'],
        ];
    }

    /**
     * @param array<int, int> $idsSuper
     */
    private function crearTokenCombinacion(array $idsSuper): string
    {
        return sha1(implode('-', $idsSuper));
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
