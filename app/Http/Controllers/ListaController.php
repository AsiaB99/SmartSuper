<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListaRequest;
use App\Http\Requests\StoreProductoListaRequest;
use App\Http\Requests\UpdateListaRequest;
use App\Http\Requests\UpdateProductoListaRequest;
use App\Models\Lista;
use App\Models\Producto;
use App\Services\RecommendationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ListaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Lista::class, 'lista');
    }

    public function index(): View
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = request()->user();

        $listas = Lista::query()
            ->when(! $usuario->isAdmin(), function ($query) use ($usuario) {
                $query->whereHas('usuarios', function ($subQuery) use ($usuario) {
                    $subQuery->where('users.id', $usuario->id);
                });
            })
            ->orderByDesc('fecha_creacion')
            ->get();

        return view('listas.index', compact('listas'));
    }

    public function create(): View
    {
        return view('listas.create');
    }

    public function store(StoreListaRequest $request): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        $lista = Lista::create($request->validated());
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista creada correctamente.');
    }

    public function edit(Lista $lista): View
    {
        return view('listas.edit', compact('lista'));
    }

    public function update(UpdateListaRequest $request, Lista $lista): RedirectResponse
    {
        $lista->update($request->validated());

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista actualizada correctamente.');
    }

    public function destroy(Lista $lista): RedirectResponse
    {
        $lista->delete();

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista eliminada correctamente.');
    }

    public function productos(Request $request, Lista $lista): View
    {
        $this->authorize('view', $lista);

        $busqueda = trim((string) $request->query('q', ''));
        $puedeEditar = $request->user()?->can('update', $lista) ?? false;

        $lista->load([
            'productos' => fn ($query) => $query
                ->when($busqueda !== '', function ($subQuery) use ($busqueda) {
                    $subQuery->where('nombre_producto', 'like', '%'.$busqueda.'%');
                })
                ->orderBy('nombre_producto'),
        ]);

        $productos = Producto::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where('nombre_producto', 'like', '%'.$busqueda.'%');
            })
            ->orderBy('nombre_producto')
            ->get();

        return view('listas.productos', compact('lista', 'productos', 'busqueda', 'puedeEditar'));
    }

    public function agregarProducto(StoreProductoListaRequest $request, Lista $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $data = $request->validated();
        $productoId = (int) $data['id_producto'];
        $cantidad = (int) $data['cantidad'];

        $productoEnLista = $lista->productos()
            ->where('productos.id', $productoId)
            ->first();
        $cantidadActual = (int) ($productoEnLista?->pivot?->cantidad ?? 0);
        $marcadoActual = (bool) ($productoEnLista?->pivot?->marcado ?? false);

        $lista->productos()->syncWithoutDetaching([
            $productoId => ['cantidad' => $cantidadActual + $cantidad, 'marcado' => $marcadoActual],
        ]);

        $mensaje = $cantidadActual > 0
            ? 'Producto ya existente: se ha sumado la cantidad.'
            : 'Producto añadido a la lista.';

        return redirect()
            ->route('listas.productos', $lista)
            ->with('status', $mensaje);
    }

    public function actualizarProducto(UpdateProductoListaRequest $request, Lista $lista, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $lista);

        if (! $lista->productos()->where('productos.id', $producto->id)->exists()) {
            abort(404);
        }

        $data = $request->validated();
        $cantidad = (int) $data['cantidad'];
        $marcado = (bool) ($data['marcado'] ?? false);

        $lista->productos()->updateExistingPivot($producto->id, [
            'cantidad' => $cantidad,
            'marcado' => $marcado,
        ]);

        return redirect()
            ->route('listas.productos', $lista)
            ->with('status', 'Producto de la lista actualizado correctamente.');
    }

    public function quitarProducto(Lista $lista, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $lista);

        $lista->productos()->detach($producto->id);

        return redirect()
            ->route('listas.productos', $lista)
            ->with('status', 'Producto quitado de la lista.');
    }

    public function finalizar(Lista $lista): RedirectResponse
    {
        $this->authorize('update', $lista);

        $lista->update([
            'estado' => 'comprada',
        ]);

        return redirect()
            ->route('listas.recomendacion', $lista)
            ->with('status', 'Lista finalizada. Se ha calculado la recomendacion de supermercado.');
    }

    public function recomendacion(Lista $lista, RecommendationService $recommendationService): View
    {
        $this->authorize('view', $lista);

        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = request()->user();

        $ranking = $recommendationService->recomendarSupermercados($lista, $usuario);

        $comparativaAhorro = null;

        if (count($ranking) >= 2) {
            $mejorOpcion = $ranking[0];
            $segundaOpcion = $ranking[1];
            $ahorroAbsoluto = max(0, (float) $segundaOpcion['score'] - (float) $mejorOpcion['score']);
            $ahorroPorcentaje = (float) $segundaOpcion['score'] > 0
                ? ($ahorroAbsoluto / (float) $segundaOpcion['score']) * 100
                : 0.0;

            $comparativaAhorro = [
                'mejor_super' => (string) $mejorOpcion['nombre_super'],
                'segunda_super' => (string) $segundaOpcion['nombre_super'],
                'ahorro_absoluto' => round($ahorroAbsoluto, 2),
                'ahorro_porcentaje' => round($ahorroPorcentaje, 2),
            ];
        }

        return view('listas.recomendacion', compact('lista', 'ranking', 'comparativaAhorro'));
    }
}
