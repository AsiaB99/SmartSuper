<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupermercadoRequest;
use App\Http\Requests\UpdateSupermercadoRequest;
use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupermercadoController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->string('busqueda'));

        $baseQuery = Supermercado::query()
            ->when($busqueda !== '', function ($query) use ($busqueda): void {
                $query->where(function ($nestedQuery) use ($busqueda): void {
                    $nestedQuery->where('nombre_super', 'like', "%{$busqueda}%")
                        ->orWhere('direccion', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('nombre_super');

        $supermercadosMapa = (clone $baseQuery)
            ->get(['id', 'nombre_super', 'direccion', 'latitud', 'longitud']);

        $latitudes = $supermercadosMapa->pluck('latitud')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value): float => (float) $value);
        $longitudes = $supermercadosMapa->pluck('longitud')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value): float => (float) $value);

        $minLat = $latitudes->min() ?? 0.0;
        $maxLat = $latitudes->max() ?? 0.0;
        $minLng = $longitudes->min() ?? 0.0;
        $maxLng = $longitudes->max() ?? 0.0;

        $markers = $supermercadosMapa
            ->filter(fn (Supermercado $supermercado) => $supermercado->latitud !== null && $supermercado->longitud !== null)
            ->map(function (Supermercado $supermercado) use ($minLat, $maxLat, $minLng, $maxLng): array {
                $latRange = max($maxLat - $minLat, 0.000001);
                $lngRange = max($maxLng - $minLng, 0.000001);

                return [
                    'id' => $supermercado->id,
                    'nombre' => $supermercado->nombre_super,
                    'top' => 12 + (76 * (1 - (((float) $supermercado->latitud - $minLat) / $latRange))),
                    'left' => 12 + (76 * (((float) $supermercado->longitud - $minLng) / $lngRange)),
                ];
            });

        $supermercados = (clone $baseQuery)->paginate(15)->withQueryString();

        return view('supermercados.index', [
            'busqueda' => $busqueda,
            'markers' => $markers,
            'supermercados' => $supermercados,
            'supermercadosMapa' => $supermercadosMapa,
        ]);
    }

    public function create(): View
    {
        return view('supermercados.create');
    }

    public function store(StoreSupermercadoRequest $request): RedirectResponse
    {
        Supermercado::create($request->validated());

        return redirect()
            ->route('supermercados.index')
            ->with('status', __('flash.supermercados.created'));
    }

    public function edit(Supermercado $supermercado): View
    {
        return view('supermercados.edit', compact('supermercado'));
    }

    public function update(UpdateSupermercadoRequest $request, Supermercado $supermercado): RedirectResponse
    {
        $supermercado->update($request->validated());

        return redirect()
            ->route('supermercados.index')
            ->with('status', __('flash.supermercados.updated'));
    }

    public function destroy(Supermercado $supermercado): RedirectResponse
    {
        $supermercado->delete();

        return redirect()
            ->route('supermercados.index')
            ->with('status', __('flash.supermercados.deleted'));
    }
}
