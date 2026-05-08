<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListaRequest;
use App\Http\Requests\StoreProductoListaRequest;
use App\Http\Requests\UpdateListaRequest;
use App\Http\Requests\UpdateProductoListaRequest;
use App\Models\Despensa;
use App\Models\Lista;
use App\Models\Producto;
use App\Models\Supermercado;
use App\Services\RecommendationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $data = $request->safe()->only(['nombre_lista', 'estado']);
        $data['fecha_creacion'] = now();

        $lista = Lista::create($data);
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista creada correctamente.');
    }

    public function edit(Lista $lista): View
    {
        return view('listas.edit', compact('lista'));
    }

    public function show(Lista $lista): View
    {
        $this->authorize('view', $lista);

        $lista->load([
            'productos' => fn ($query) => $query->orderBy('nombre_producto'),
            'supermercadoElegido:id,nombre_super',
        ]);

        return view('listas.show', compact('lista'));
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

    public function productos(Request $request, Lista $lista): View|JsonResponse
    {
        $this->authorize('view', $lista);

        $busqueda = trim((string) $request->query('q', ''));
        $puedeEditar = $request->user()?->can('update', $lista) ?? false;

        $lista->load([
            'productos' => fn ($query) => $query
                ->when($busqueda !== '', fn ($subQuery) => $this->aplicarBusquedaProducto($subQuery, $busqueda))
                ->orderBy('nombre_producto'),
        ]);

        $productos = Producto::query()
            ->when($busqueda !== '', fn ($query) => $this->aplicarBusquedaProducto($query, $busqueda))
            ->orderBy('nombre_producto')
            ->paginate(9)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'catalogo' => view('listas.partials.catalogo-productos', [
                    'lista' => $lista,
                    'productos' => $productos,
                    'puedeEditar' => $puedeEditar,
                ])->render(),
            ]);
        }

        return view('listas.productos', compact('lista', 'productos', 'busqueda', 'puedeEditar'));
    }

    public function sugerenciasProductos(Request $request, Lista $lista): JsonResponse
    {
        $this->authorize('view', $lista);

        $busqueda = trim((string) $request->query('q', ''));

        if ($busqueda === '') {
            return response()->json([]);
        }

        return response()->json($this->obtenerSugerenciasProducto($busqueda));
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
        $marcado = array_key_exists('marcado', $data)
            ? (bool) $data['marcado']
            : (bool) ($lista->productos()->where('productos.id', $producto->id)->first()?->pivot?->marcado ?? false);

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

        $data = request()->validate([
            'id_despensa' => ['nullable', 'integer'],
        ]);

        $lista->update([
            'estado' => 'comprada',
        ]);

        $agregarADespensa = ! empty($data['id_despensa']);
        $despensa = null;

        if ($agregarADespensa) {
            $despensaId = (int) ($data['id_despensa'] ?? 0);

            if ($despensaId <= 0) {
                return redirect()
                    ->back()
                    ->withErrors(['id_despensa' => 'Selecciona una despensa para continuar.'])
                    ->withInput();
            }

            $despensa = Despensa::query()->findOrFail($despensaId);
            $this->authorize('update', $despensa);

            $productosLista = $lista->productos()->get();

            DB::transaction(function () use ($despensa, $productosLista): void {
                foreach ($productosLista as $producto) {
                    $cantidad = (int) ($producto->pivot?->cantidad ?? 0);
                    if ($cantidad <= 0) {
                        continue;
                    }

                    $stockActual = (int) ($despensa->productos()
                        ->where('productos.id', $producto->id)
                        ->first()?->pivot?->stock ?? 0);

                    $despensa->productos()->syncWithoutDetaching([
                        $producto->id => ['stock' => $stockActual + $cantidad],
                    ]);
                }
            });
        }

        $mensaje = $agregarADespensa
            ? 'Lista finalizada y productos agregados a la despensa.'
            : 'Lista finalizada. Se ha calculado la recomendacion de supermercado.';

        return redirect()
            ->route('listas.recomendacion', $lista)
            ->with('status', $mensaje);
    }

    public function confirmarFinalizacion(Lista $lista): View
    {
        $this->authorize('update', $lista);

        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = request()->user();

        $despensasEditables = Despensa::query()
            ->when(! $usuario->isAdmin(), function ($query) use ($usuario) {
                $query->whereHas('usuarios', function ($subQuery) use ($usuario) {
                    $subQuery->where('users.id', $usuario->id)
                        ->whereIn('tienen.permiso_despensa', ['owner', 'editor']);
                });
            })
            ->orderBy('nombre_despensa')
            ->get(['id', 'nombre_despensa']);

        return view('listas.finalizar', compact('lista', 'despensasEditables'));
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

    public function elegirSupermercado(Request $request, Lista $lista, RecommendationService $recommendationService): RedirectResponse
    {
        $this->authorize('update', $lista);

        $data = $request->validate([
            'id_supermercado' => ['required', 'integer', 'exists:supermercados,id'],
        ]);

        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        if ($usuario->latitud === null || $usuario->longitud === null) {
            throw ValidationException::withMessages([
                'ubicacion' => 'Necesitas compartir tu ubicación para elegir una recomendación.',
            ]);
        }

        $ranking = $recommendationService->recomendarSupermercados($lista, $usuario);
        $idsDisponibles = collect($ranking)->pluck('id_super')->map(fn ($id) => (int) $id);
        $idSupermercado = (int) $data['id_supermercado'];

        if (! $idsDisponibles->contains($idSupermercado)) {
            throw ValidationException::withMessages([
                'id_supermercado' => 'La opción seleccionada no está disponible para esta lista.',
            ]);
        }

        $supermercado = Supermercado::query()->findOrFail($idSupermercado);
        $lista->update([
            'id_supermercado_elegido' => $supermercado->id,
        ]);

        return redirect()
            ->route('listas.finalizar.confirmar', $lista)
            ->with('status', 'Supermercado seleccionado: '.$supermercado->nombre_super.'. Ya puedes finalizar la compra.');
    }

    private function aplicarBusquedaProducto(Builder $query, string $busqueda): void
    {
        $query->where(function (Builder $subQuery) use ($busqueda): void {
            $subQuery
                ->where('nombre_producto', 'like', '%'.$busqueda.'%')
                ->orWhere('marca', 'like', '%'.$busqueda.'%')
                ->orWhere('formato', 'like', '%'.$busqueda.'%');
        });
    }

    private function obtenerSugerenciasProducto(string $busqueda): array
    {
        if ($busqueda === '') {
            return [];
        }

        return Producto::query()
            ->select('nombre_producto')
            ->where(function (Builder $query) use ($busqueda): void {
                $query
                    ->where('nombre_producto', 'like', '%'.$busqueda.'%')
                    ->orWhere('marca', 'like', '%'.$busqueda.'%')
                    ->orWhere('formato', 'like', '%'.$busqueda.'%');
            })
            ->distinct()
            ->orderBy('nombre_producto')
            ->limit(8)
            ->pluck('nombre_producto')
            ->all();
    }
}
