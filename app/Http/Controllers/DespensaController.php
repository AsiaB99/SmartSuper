<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockDespensaRequest;
use App\Http\Requests\StoreDespensaRequest;
use App\Http\Requests\UpdateStockDespensaRequest;
use App\Http\Requests\UpdateDespensaRequest;
use App\Models\Despensa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ->with('status', __('flash.despensas.created'));
    }

    public function edit(Request $request, Despensa $despensa): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()
                ->route('despensas.index')
                ->with('status', __('flash.despensas.edit_from_modal'));
        }

        return response()->json($this->obtenerDatosEdicion($despensa, $usuario));
    }

    public function update(UpdateDespensaRequest $request, Despensa $despensa): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();
        $permisoDespensa = $usuario->permisoEnDespensa($despensa);
        $puedeAsignarEditores = $usuario->isAdmin() || $permisoDespensa === 'owner';

        $data = $request->validated();
        $editoresNuevos = collect($data['usuarios_editores'] ?? [])
            ->map(fn ($nombreUsuario) => trim((string) $nombreUsuario))
            ->filter(fn ($nombreUsuario) => $nombreUsuario !== '')
            ->unique()
            ->values()
            ->all();

        unset($data['usuarios_editores']);

        $despensa->update($data);

        if ($puedeAsignarEditores && $editoresNuevos !== []) {
            $owners = $despensa->usuarios()
                ->wherePivot('permiso_despensa', 'owner')
                ->get(['users.id', 'users.nombre_usuario']);

            $idsOwner = $owners
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $usuariosEncontrados = User::query()
                ->whereIn('nombre_usuario', $editoresNuevos)
                ->get(['id', 'nombre_usuario']);

            $nombresEncontrados = $usuariosEncontrados
                ->pluck('nombre_usuario')
                ->filter()
                ->values()
                ->all();

            $nombresNoEncontrados = collect($editoresNuevos)
                ->reject(fn ($nombreUsuario) => in_array($nombreUsuario, $nombresEncontrados, true))
                ->values();

            if ($nombresNoEncontrados->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'usuarios_editores' => [__('flash.despensas.editors_not_found', ['names' => $nombresNoEncontrados->implode(', ')])],
                ]);
            }

            $payloadEditores = $usuariosEncontrados
                ->reject(fn (User $editor) => in_array((int) $editor->id, $idsOwner, true))
                ->mapWithKeys(fn (User $editor) => [(int) $editor->id => ['permiso_despensa' => 'editor']])
                ->all();

            if ($payloadEditores !== []) {
                $despensa->usuarios()->syncWithoutDetaching($payloadEditores);
            }
        }

        return redirect()
            ->route('despensas.index')
            ->with('status', __('flash.despensas.updated'));
    }

    public function destroy(Despensa $despensa): RedirectResponse
    {
        $despensa->delete();

        return redirect()
            ->route('despensas.index')
            ->with('status', __('flash.despensas.deleted'));
    }

    public function stock(Request $request, Despensa $despensa): View
    {
        $this->authorize('view', $despensa);

        $busqueda = trim((string) $request->query('q', ''));
        $puedeEditar = $request->user()?->can('update', $despensa) ?? false;
        $stockBaseQuery = $despensa->productos();

        $totalProductos = (clone $stockBaseQuery)->count();
        $productosBajos = (clone $stockBaseQuery)->wherePivot('stock', '<=', 1)->count();
        $unidadesTotales = (clone $stockBaseQuery)->sum('almacena.stock');

        $despensa->load([
            'productos' => fn ($query) => $query
                ->when($busqueda !== '', function ($subQuery) use ($busqueda) {
                    $subQuery->where('nombre_producto', 'like', '%'.$busqueda.'%');
                })
                ->orderBy('nombre_producto'),
        ]);
        $productos = Producto::query()
            ->orderBy('nombre_producto')
            ->get();

        return view('despensas.stock', compact(
            'despensa',
            'productos',
            'busqueda',
            'puedeEditar',
            'totalProductos',
            'productosBajos',
            'unidadesTotales',
        ));
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
            ->with('status', __('flash.despensas.product_added'));
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
                ->with('status', __('flash.despensas.product_removed'));
        }

        $despensa->productos()->updateExistingPivot($producto->id, ['stock' => $stock]);

        return redirect()
            ->route('despensas.stock', $despensa)
            ->with('status', __('flash.despensas.stock_updated'));
    }

    public function quitarProducto(Despensa $despensa, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $despensa);

        $despensa->productos()->detach($producto->id);

        return redirect()
            ->route('despensas.stock', $despensa)
            ->with('status', __('flash.despensas.product_removed'));
    }

    /**
     * @return array{despensa: array{id: int, nombre_despensa: string}, puedeAsignarEditores: bool, usuariosEditoresActuales: array<int, string>}
     */
    private function obtenerDatosEdicion(Despensa $despensa, User $usuario): array
    {
        $permisoDespensa = $usuario->permisoEnDespensa($despensa);
        $puedeAsignarEditores = $usuario->isAdmin() || $permisoDespensa === 'owner';
        $usuariosEditoresActuales = [];

        if ($puedeAsignarEditores) {
            $usuariosEditoresActuales = $despensa->usuarios()
                ->wherePivot('permiso_despensa', 'editor')
                ->orderBy('nombre_usuario')
                ->pluck('users.nombre_usuario')
                ->filter()
                ->values()
                ->all();
        }

        return [
            'despensa' => [
                'id' => $despensa->id,
                'nombre_despensa' => $despensa->nombre_despensa,
            ],
            'puedeAsignarEditores' => $puedeAsignarEditores,
            'usuariosEditoresActuales' => $usuariosEditoresActuales,
        ];
    }
}
