<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupermercadoRequest;
use App\Http\Requests\UpdateSupermercadoRequest;
use App\Models\CadenaSupermercado;
use App\Models\Supermercado;
use App\Services\ActualizarEstadoCadenaSupermercadoService;
use App\Services\GeocodificacionDireccionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SupermercadoController extends Controller
{
    private const LIMITE_MARCADORS_MAPA = 300;
    private const RADIO_BUSQUEDA_KM = 30.0;
    private const MAX_BUSQUEDA_LEN = 120;
    private const MAX_DIRECCION_LEN = 180;

    public function index(Request $request): View
    {
        $busqueda = mb_substr(trim((string) $request->string('busqueda')), 0, self::MAX_BUSQUEDA_LEN);
        $direccionPostal = mb_substr(trim((string) $request->string('direccion_postal')), 0, self::MAX_DIRECCION_LEN);
        [$latitudUsuario, $longitudUsuario, $mensajeUbicacion] = $this->resolverUbicacionUsuario($request, $direccionPostal);

        $baseQuery = Supermercado::query()
            ->where('activo', true)
            ->when($busqueda !== '', function ($query) use ($busqueda): void {
                $query->where(function ($nestedQuery) use ($busqueda): void {
                    $nestedQuery->where('nombre_super', 'like', "%{$busqueda}%")
                        ->orWhere('direccion', 'like', "%{$busqueda}%");
                });
            });

        $ordenarPorCercania = $latitudUsuario !== null && $longitudUsuario !== null;
        $supermercadosFiltrados = collect();
        $supermercadosMapa = collect();
        $markers = collect();
        $totalSupermercados = 0;
        $supermercados = $this->paginarColeccion(collect(), 15, $request);

        if ($ordenarPorCercania) {
            $supermercadosFiltrados = $this->buscarSupermercadosEnRadio(
                clone $baseQuery,
                $latitudUsuario,
                $longitudUsuario
            );

            $totalSupermercados = $supermercadosFiltrados->count();
            $supermercadosMapa = $supermercadosFiltrados->take(self::LIMITE_MARCADORS_MAPA)->values();
            $markers = $this->crearMarkers($supermercadosMapa);
            $supermercados = $this->paginarColeccion($supermercadosFiltrados, 15, $request);
        }

        return view('supermercados.index', [
            'busqueda' => $busqueda,
            'direccionPostal' => $direccionPostal,
            'latitudUsuario' => $latitudUsuario,
            'longitudUsuario' => $longitudUsuario,
            'mensajeUbicacion' => $mensajeUbicacion,
            'markers' => $markers,
            'ordenarPorCercania' => $ordenarPorCercania,
            'radioBusquedaKm' => self::RADIO_BUSQUEDA_KM,
            'supermercados' => $supermercados,
            'supermercadosMapa' => $supermercadosMapa,
            'totalSupermercados' => $totalSupermercados,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => 'supermercados']);
    }

    public function store(StoreSupermercadoRequest $request): RedirectResponse
    {
        Supermercado::create($request->validated());

        return redirect()
            ->route('admin.index', ['tab' => 'supermercados'])
            ->with('status', __('flash.supermercados.created'));
    }

    public function edit(Supermercado $supermercado): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => 'supermercados']);
    }

    public function update(UpdateSupermercadoRequest $request, Supermercado $supermercado): RedirectResponse
    {
        $supermercado->update($request->validated());

        return redirect()
            ->route('admin.index', ['tab' => 'supermercados'])
            ->with('status', __('flash.supermercados.updated'));
    }

    public function destroy(Request $request, Supermercado $supermercado): RedirectResponse
    {
        $supermercado->delete();

        return redirect()
            ->route('admin.index', array_filter([
                'tab' => 'supermercados',
                'supermercados_busqueda' => trim((string) $request->query('supermercados_busqueda', '')),
                'supermercados_page' => $request->query('supermercados_page'),
            ], static fn ($value): bool => $value !== null && $value !== ''))
            ->with('status', __('flash.supermercados.deleted'));
    }

    public function toggleActivo(Request $request, Supermercado $supermercado): RedirectResponse
    {
        $supermercado->update([
            'activo' => ! $supermercado->activo,
        ]);

        return redirect()
            ->route('admin.index', $this->buildAdminRedirectQuery($request))
            ->with('status', __('flash.admin.supermercados.toggled_store'));
    }

    public function toggleCadenaActivo(
        Request $request,
        CadenaSupermercado $cadenaSupermercado,
        ActualizarEstadoCadenaSupermercadoService $service
    ): RedirectResponse {
        $accion = (string) $request->input('chain_action', '');

        if ($accion === 'activate') {
            $activar = true;
        } elseif ($accion === 'deactivate') {
            $activar = false;
        } else {
            $activar = $cadenaSupermercado->supermercados()
                ->where('activo', false)
                ->exists();
        }

        $service->actualizar($cadenaSupermercado, $activar);

        return redirect()
            ->route('admin.index', $this->buildAdminRedirectQuery($request))
            ->with('status', __('flash.admin.supermercados.toggled_chain', [
                'name' => $cadenaSupermercado->nombre,
            ]));
    }

    /**
     * @return array{float|null, float|null, string|null}
     */
    private function resolverUbicacionUsuario(Request $request, string $direccionPostal): array
    {
        $latitudQuery = $request->query('latitud');
        $longitudQuery = $request->query('longitud');

        if (is_numeric($latitudQuery) && is_numeric($longitudQuery)) {
            $latitud = (float) $latitudQuery;
            $longitud = (float) $longitudQuery;

            if ($latitud >= -90 && $latitud <= 90 && $longitud >= -180 && $longitud <= 180) {
                return [$latitud, $longitud, null];
            }
        }

        if ($direccionPostal !== '') {
            $coordenadas = app(GeocodificacionDireccionService::class)->buscarCoordenadas($direccionPostal);

            if ($coordenadas !== null) {
                return [$coordenadas['latitud'], $coordenadas['longitud'], null];
            }

            return [null, null, __('supermercados.index.location_prompt.address_error')];
        }

        $usuario = $request->user();

        if ($usuario?->latitud === null || $usuario?->longitud === null) {
            return [null, null, null];
        }

        return [(float) $usuario->latitud, (float) $usuario->longitud, null];
    }

    private function buscarSupermercadosEnRadio($query, float $latitudUsuario, float $longitudUsuario): Collection
    {
        [$minLat, $maxLat, $minLng, $maxLng] = $this->boundingBox($latitudUsuario, $longitudUsuario, self::RADIO_BUSQUEDA_KM);

        return $query
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->whereBetween('latitud', [$minLat, $maxLat])
            ->whereBetween('longitud', [$minLng, $maxLng])
            ->get(['id', 'nombre_super', 'direccion', 'latitud', 'longitud'])
            ->map(function (Supermercado $supermercado) use ($latitudUsuario, $longitudUsuario): Supermercado {
                $supermercado->setAttribute('distancia_km', round($this->haversineKm(
                    $latitudUsuario,
                    $longitudUsuario,
                    (float) $supermercado->latitud,
                    (float) $supermercado->longitud
                ), 2));

                return $supermercado;
            })
            ->filter(fn (Supermercado $supermercado): bool => (float) $supermercado->distancia_km <= self::RADIO_BUSQUEDA_KM)
            ->sort(function (Supermercado $a, Supermercado $b): int {
                $distancia = (float) $a->distancia_km <=> (float) $b->distancia_km;

                if ($distancia !== 0) {
                    return $distancia;
                }

                return strcasecmp($a->nombre_super, $b->nombre_super);
            })
            ->values();
    }

    private function crearMarkers(Collection $supermercadosMapa): Collection
    {
        $latitudes = $supermercadosMapa->pluck('latitud')
            ->map(fn ($value): float => (float) $value);
        $longitudes = $supermercadosMapa->pluck('longitud')
            ->map(fn ($value): float => (float) $value);

        $minLat = $latitudes->min() ?? 0.0;
        $maxLat = $latitudes->max() ?? 0.0;
        $minLng = $longitudes->min() ?? 0.0;
        $maxLng = $longitudes->max() ?? 0.0;

        return $supermercadosMapa->map(function (Supermercado $supermercado) use ($minLat, $maxLat, $minLng, $maxLng): array {
            $latRange = max($maxLat - $minLat, 0.000001);
            $lngRange = max($maxLng - $minLng, 0.000001);

            return [
                'id' => $supermercado->id,
                'nombre' => $supermercado->nombre_super,
                'direccion' => $supermercado->direccion,
                'latitud' => (float) $supermercado->latitud,
                'longitud' => (float) $supermercado->longitud,
                'top' => 12 + (76 * (1 - (((float) $supermercado->latitud - $minLat) / $latRange))),
                'left' => 12 + (76 * (((float) $supermercado->longitud - $minLng) / $lngRange)),
            ];
        });
    }

    private function paginarColeccion(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max((int) $request->integer('page', 1), 1);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    /**
     * @return array{float, float, float, float}
     */
    private function boundingBox(float $latitud, float $longitud, float $radioKm): array
    {
        $deltaLat = $radioKm / 111.32;
        $coseno = max(cos(deg2rad($latitud)), 0.01);
        $deltaLng = $radioKm / (111.32 * $coseno);

        return [
            max(-90.0, $latitud - $deltaLat),
            min(90.0, $latitud + $deltaLat),
            max(-180.0, $longitud - $deltaLng),
            min(180.0, $longitud + $deltaLng),
        ];
    }

    private function haversineKm(float $latitudOrigen, float $longitudOrigen, float $latitudDestino, float $longitudDestino): float
    {
        $radioTierraKm = 6371.0;
        $dLat = deg2rad($latitudDestino - $latitudOrigen);
        $dLon = deg2rad($longitudDestino - $longitudOrigen);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitudOrigen))
            * cos(deg2rad($latitudDestino))
            * sin($dLon / 2) ** 2;

        return $radioTierraKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function buildAdminRedirectQuery(Request $request): array
    {
        return array_filter([
            'tab' => 'supermercados',
            'supermercados_busqueda' => trim((string) $request->query('supermercados_busqueda', '')),
            'supermercados_page' => $request->query('supermercados_page'),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }
}
