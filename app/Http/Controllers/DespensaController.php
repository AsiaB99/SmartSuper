<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockDespensaRequest;
use App\Http\Requests\StoreDespensaRequest;
use App\Http\Requests\UpdateStockDespensaRequest;
use App\Http\Requests\UpdateDespensaRequest;
use App\Models\Despensa;
use App\Models\Producto;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class DespensaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Despensa::class, 'despensa');
    }

    public function index(): View
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = request()->user();

        $despensas = Despensa::query()
            ->when(! $usuario->isAdmin(), function ($query) use ($usuario) {
                $query->whereHas('usuarios', function ($subQuery) use ($usuario) {
                    $subQuery->where('users.id', $usuario->id);
                });
            })
            ->orderByDesc('fecha_creacion')
            ->get();

        return view('despensas.index', compact('despensas'));
    }

    public function create(): View
    {
        return view('despensas.create');
    }

    public function store(StoreDespensaRequest $request): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        $despensa = Despensa::create($request->validated());
        $despensa->usuarios()->attach($usuario->id, ['permiso_despensa' => 'owner']);

        return redirect()
            ->route('despensas.index')
            ->with('status', 'Despensa creada correctamente.');
    }

    public function edit(Despensa $despensa): View
    {
        return view('despensas.edit', compact('despensa'));
    }

    public function update(UpdateDespensaRequest $request, Despensa $despensa): RedirectResponse
    {
        $despensa->update($request->validated());

        return redirect()
            ->route('despensas.index')
            ->with('status', 'Despensa actualizada correctamente.');
    }

    public function destroy(Despensa $despensa): RedirectResponse
    {
        $despensa->delete();

        return redirect()
            ->route('despensas.index')
            ->with('status', 'Despensa eliminada correctamente.');
    }

    public function stock(Request $request, Despensa $despensa): View
    {
        $this->authorize('view', $despensa);

        $busqueda = trim((string) $request->query('q', ''));
        $puedeEditar = $request->user()?->can('update', $despensa) ?? false;

        $despensa->load([
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

        return view('despensas.stock', compact('despensa', 'productos', 'busqueda', 'puedeEditar'));
    }

    public function agregarProducto(StoreStockDespensaRequest $request, Despensa $despensa): RedirectResponse
    {
        $this->authorize('update', $despensa);

        $data = $request->validated();
        $productoId = (int) $data['id_producto'];
        $stock = (int) $data['stock'];

        $stockActual = (int) ($despensa->productos()
            ->where('productos.id', $productoId)
            ->first()?->pivot?->stock ?? 0);

        $despensa->productos()->syncWithoutDetaching([
            $productoId => ['stock' => $stockActual + $stock],
        ]);

        return redirect()
            ->route('despensas.stock', $despensa)
            ->with('status', 'Producto anadido al stock.');
    }

    public function actualizarStock(UpdateStockDespensaRequest $request, Despensa $despensa, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $despensa);

        if (! $despensa->productos()->where('productos.id', $producto->id)->exists()) {
            abort(404);
        }

        $stock = (int) $request->validated()['stock'];

        if ($stock === 0) {
            $despensa->productos()->detach($producto->id);

            return redirect()
                ->route('despensas.stock', $despensa)
                ->with('status', 'Producto eliminado del stock.');
        }

        $despensa->productos()->updateExistingPivot($producto->id, ['stock' => $stock]);

        return redirect()
            ->route('despensas.stock', $despensa)
            ->with('status', 'Stock actualizado correctamente.');
    }

    public function quitarProducto(Despensa $despensa, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $despensa);

        $despensa->productos()->detach($producto->id);

        return redirect()
            ->route('despensas.stock', $despensa)
            ->with('status', 'Producto eliminado del stock.');
    }
}