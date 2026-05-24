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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $stockPorDespensa = DB::table('almacena')
            ->select(
                'id_despensa',
                DB::raw('SUM(stock) as unidades_totales'),
                DB::raw('COUNT(*) as productos_con_stock'),
                DB::raw('SUM(CASE WHEN stock <= 1 THEN 1 ELSE 0 END) as productos_stock_bajo')
            )
            ->whereIn('id_despensa', $despensas->pluck('id'))
            ->groupBy('id_despensa')
            ->get()
            ->keyBy('id_despensa');

        $resumenStock = $despensas->mapWithKeys(function (Despensa $despensa) use ($stockPorDespensa): array {
            $metricas = $stockPorDespensa->get($despensa->id);
            $productosConStock = max(0, (int) ($metricas->productos_con_stock ?? 0));
            $unidadesTotales = max(0, (int) ($metricas->unidades_totales ?? 0));
            $productosStockBajo = max(0, (int) ($metricas->productos_stock_bajo ?? 0));
            $objetivoUnidades = max(1, $productosConStock * 5);
            $porcentaje = $productosConStock === 0
                ? 0
                : max(8, min(100, (int) round(($unidadesTotales / $objetivoUnidades) * 100)));

            $barClass = $porcentaje < 35
                ? 'bg-rose-500'
                : ($porcentaje < 70 ? 'bg-amber-500' : 'bg-brand-500');

            return [
                $despensa->id => [
                    'porcentaje' => $porcentaje,
                    'bar_class' => $barClass,
                    'productos' => $productosConStock,
                    'stock_bajo' => $productosStockBajo,
                ],
            ];
        })->all();

        return view('despensas.index', compact('despensas', 'resumenStock'));
    }

    public function store(StoreDespensaRequest $request): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        $data = $request->safe()->only(['nombre_despensa']);
        $data['fecha_creacion'] = now();

        $despensa = Despensa::create($data);
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

    public function stock(Request $request, Despensa $despensa): View|JsonResponse
    {
        $this->authorize('view', $despensa);

        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();
        $busqueda = trim((string) $request->query('q', ''));
        $lowStockThreshold = max(1, min(99, (int) $request->query('low_stock_threshold', 1)));
        $puedeEditar = $usuario?->can('update', $despensa) ?? false;
        $stockBaseQuery = $despensa->productos();
        $listasEditables = $this->obtenerListasEditables($usuario);

        $totalProductos = (clone $stockBaseQuery)->count();
        $productosBajos = (clone $stockBaseQuery)->wherePivot('stock', '<=', $lowStockThreshold)->count();
        $unidadesTotales = (clone $stockBaseQuery)->sum('almacena.stock');

        $despensa->load([
            'productos' => fn ($query) => $query
                ->when($busqueda !== '', fn ($subQuery) => $this->aplicarBusquedaProducto($subQuery, $busqueda))
                ->orderBy('nombre_producto'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'productos' => view('despensas.partials.stock-productos', [
                    'despensa' => $despensa,
                    'puedeEditar' => $puedeEditar,
                    'lowStockThreshold' => $lowStockThreshold,
                    'tieneListasEditables' => $listasEditables->isNotEmpty(),
                ])->render(),
                'query' => $busqueda,
            ]);
        }
        $productoManualSeleccionado = null;
        $productoManualSeleccionadoId = (int) session()->getOldInput('id_producto', 0);

        if ($productoManualSeleccionadoId > 0) {
            $productoManualSeleccionado = Producto::query()
                ->select('id', 'nombre_producto', 'marca', 'formato')
                ->find($productoManualSeleccionadoId);
        }

        return view('despensas.stock', compact(
            'despensa',
            'busqueda',
            'puedeEditar',
            'totalProductos',
            'productosBajos',
            'unidadesTotales',
            'lowStockThreshold',
            'listasEditables',
            'productoManualSeleccionado',
        ));
    }

    public function sugerenciasStock(Request $request, Despensa $despensa): JsonResponse
    {
        $this->authorize('view', $despensa);

        $busqueda = trim((string) $request->query('q', ''));
        if ($busqueda === '') {
            return response()->json([]);
        }

        return response()->json($this->obtenerSugerenciasProductoEnDespensa($despensa, $busqueda));
    }

    public function sugerenciasCatalogoProductos(Request $request, Despensa $despensa): JsonResponse
    {
        $this->authorize('update', $despensa);

        $busqueda = trim((string) $request->query('q', ''));

        if ($busqueda === '') {
            return response()->json([]);
        }

        return response()->json($this->obtenerSugerenciasCatalogoProductos($busqueda));
    }

    public function agregarProducto(StoreStockDespensaRequest $request, Despensa $despensa): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $despensa);

        $data = $request->validated();
        $productoId = (int) $data['id_producto'];
        $stock = (int) $data['stock'];
        $lowStockThreshold = max(1, min(99, (int) $request->input('low_stock_threshold', 1)));

        $stockActual = (int) ($despensa->productos()
            ->where('productos.id', $productoId)
            ->first()?->pivot?->stock ?? 0);

        $despensa->productos()->syncWithoutDetaching([
            $productoId => ['stock' => $stockActual + $stock],
        ]);

        if ($request->expectsJson()) {
            $despensa->load([
                'productos' => fn ($query) => $query->orderBy('nombre_producto'),
            ]);

            $stockBaseQuery = $despensa->productos();

            return response()->json([
                'status' => __('flash.despensas.product_added'),
                'productos' => view('despensas.partials.stock-productos', [
                    'despensa' => $despensa,
                    'puedeEditar' => true,
                    'lowStockThreshold' => $lowStockThreshold,
                    'tieneListasEditables' => $this->obtenerListasEditables($request->user())->isNotEmpty(),
                ])->render(),
                'stats' => [
                    'totalProductos' => (int) (clone $stockBaseQuery)->count(),
                    'productosBajos' => (int) (clone $stockBaseQuery)->wherePivot('stock', '<=', $lowStockThreshold)->count(),
                    'unidadesTotales' => (int) (clone $stockBaseQuery)->sum('almacena.stock'),
                ],
            ]);
        }

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

    private function aplicarBusquedaProducto(Builder $query, string $busqueda): void
    {
        $query->where(function (Builder $subQuery) use ($busqueda): void {
            $subQuery
                ->where('nombre_producto', 'like', '%'.$busqueda.'%')
                ->orWhere('marca', 'like', '%'.$busqueda.'%')
                ->orWhere('formato', 'like', '%'.$busqueda.'%');
        });
    }

    /**
     * @return array<int, string>
     */
    private function obtenerSugerenciasProductoEnDespensa(Despensa $despensa, string $busqueda): array
    {
        return $despensa->productos()
            ->select('productos.nombre_producto')
            ->where(function (Builder $query) use ($busqueda): void {
                $query
                    ->where('productos.nombre_producto', 'like', '%'.$busqueda.'%')
                    ->orWhere('productos.marca', 'like', '%'.$busqueda.'%')
                    ->orWhere('productos.formato', 'like', '%'.$busqueda.'%');
            })
            ->distinct()
            ->orderBy('productos.nombre_producto')
            ->limit(8)
            ->pluck('productos.nombre_producto')
            ->all();
    }

    /**
     * @return array<int, array{id: int, nombre: string, descripcion: string}>
     */
    private function obtenerSugerenciasCatalogoProductos(string $busqueda): array
    {
        return Producto::query()
            ->select('id', 'nombre_producto', 'marca', 'formato')
            ->where(function (Builder $query) use ($busqueda): void {
                $query
                    ->where('nombre_producto', 'like', '%'.$busqueda.'%')
                    ->orWhere('marca', 'like', '%'.$busqueda.'%')
                    ->orWhere('formato', 'like', '%'.$busqueda.'%');
            })
            ->orderBy('nombre_producto')
            ->limit(8)
            ->get()
            ->map(static function (Producto $producto): array {
                $descripcion = collect([$producto->marca, $producto->formato])
                    ->filter(static fn ($valor): bool => filled($valor))
                    ->implode(' · ');

                return [
                    'id' => (int) $producto->id,
                    'nombre' => (string) $producto->nombre_producto,
                    'descripcion' => $descripcion,
                ];
            })
            ->all();
    }

    private function obtenerListasEditables(User $usuario): \Illuminate\Support\Collection
    {
        return \App\Models\Lista::query()
            ->where('estado', 'activa')
            ->when(! $usuario->isAdmin(), function ($query) use ($usuario) {
                $query->whereHas('usuarios', function ($subQuery) use ($usuario) {
                    $subQuery->where('users.id', $usuario->id)
                        ->whereIn('hacen.permiso_lista', ['owner', 'editor']);
                });
            })
            ->orderBy('nombre_lista')
            ->get(['id', 'nombre_lista']);
    }
}
