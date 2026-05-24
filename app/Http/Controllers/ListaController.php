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
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListaController extends Controller
{
    private const CATALOGO_PRODUCTOS_POR_PAGINA = 6;

    public function __construct()
    {
        $this->authorizeResource(Lista::class, 'lista');
    }

    public function index(): View
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = request()->user();

        $listas = Lista::query()
            ->whereHas('usuarios', function ($subQuery) use ($usuario) {
                $subQuery->where('users.id', $usuario->id);
            })
            ->orderByDesc('fecha_creacion')
            ->get();

        return view('listas.index', compact('listas'));
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
            ->with('status', __('flash.listas.created'));
    }

    public function edit(Request $request, Lista $lista): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()
                ->route('listas.index')
                ->with('status', __('flash.listas.edit_from_modal'));
        }

        return response()->json($this->obtenerDatosEdicion($lista, $usuario));
    }

    public function show(Lista $lista): View
    {
        $this->authorize('view', $lista);

        $lista->load([
            'productos' => fn ($query) => $query->orderBy('nombre_producto'),
            'supermercadoElegido:id,nombre_super',
            'usuarios' => fn ($query) => $query
                ->select('users.id', 'users.name', 'users.nombre_usuario')
                ->orderByRaw("
                    case hacen.permiso_lista
                        when 'owner' then 1
                        when 'editor' then 2
                        when 'viewer' then 3
                        else 4
                    end
                ")
                ->orderBy('users.nombre_usuario')
                ->orderBy('users.name'),
        ]);

        return view('listas.show', compact('lista'));
    }

    public function update(UpdateListaRequest $request, Lista $lista): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();
        $permisoLista = $usuario->permisoEnLista($lista);
        $puedeAsignarEditores = $permisoLista === 'owner';

        $data = $request->validated();
        $editoresNuevos = collect($data['usuarios_editores'] ?? [])
            ->map(fn ($nombreUsuario) => trim((string) $nombreUsuario))
            ->filter(fn ($nombreUsuario) => $nombreUsuario !== '')
            ->unique()
            ->values()
            ->all();

        unset($data['usuarios_editores']);

        $lista->update($data);

        if ($puedeAsignarEditores && $editoresNuevos !== []) {
            $owners = $lista->usuarios()
                ->wherePivot('permiso_lista', 'owner')
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
                    'usuarios_editores' => [__('flash.listas.editors_not_found', ['names' => $nombresNoEncontrados->implode(', ')])],
                ]);
            }

            $payloadEditores = $usuariosEncontrados
                ->reject(fn (User $editor) => in_array((int) $editor->id, $idsOwner, true))
                ->mapWithKeys(fn (User $editor) => [(int) $editor->id => ['permiso_lista' => 'editor']])
                ->all();

            if ($payloadEditores !== []) {
                $lista->usuarios()->syncWithoutDetaching($payloadEditores);
            }
        }

        return redirect()
            ->route('listas.index')
            ->with('status', __('flash.listas.updated'));
    }

    public function destroy(Lista $lista): RedirectResponse
    {
        $lista->delete();

        return redirect()
            ->route('listas.index')
            ->with('status', __('flash.listas.deleted'));
    }

    /**
     * @return array{lista: array{id: int, nombre_lista: string, estado: string}, puedeAsignarEditores: bool, usuariosEditoresActuales: array<int, string>}
     */
    private function obtenerDatosEdicion(Lista $lista, User $usuario): array
    {
        $permisoLista = $usuario->permisoEnLista($lista);
        $puedeAsignarEditores = $permisoLista === 'owner';
        $usuariosEditoresActuales = [];

        if ($puedeAsignarEditores) {
            $usuariosEditoresActuales = $lista->usuarios()
                ->wherePivot('permiso_lista', 'editor')
                ->orderBy('nombre_usuario')
                ->pluck('users.nombre_usuario')
                ->filter()
                ->values()
                ->all();
        }

        return [
            'lista' => [
                'id' => $lista->id,
                'nombre_lista' => $lista->nombre_lista,
                'estado' => $lista->estado,
            ],
            'puedeAsignarEditores' => $puedeAsignarEditores,
            'usuariosEditoresActuales' => $usuariosEditoresActuales,
        ];
    }

    public function productos(Request $request, Lista $lista): View|JsonResponse
    {
        $this->authorize('view', $lista);

        $busqueda = trim((string) $request->query('q', ''));
        $hayBusqueda = $busqueda !== '';
        $puedeEditar = $request->user()?->can('update', $lista) ?? false;
        $terminosBusqueda = $this->obtenerTerminosBusqueda($busqueda);

        $lista->load([
            'productos' => fn ($query) => $query
                ->when($hayBusqueda, fn ($subQuery) => $this->aplicarBusquedaProducto($subQuery, $terminosBusqueda))
                ->orderBy('nombre_producto'),
        ]);

        $productos = $this->obtenerCatalogoProductosParaLista($busqueda);

        if ($request->expectsJson()) {
            return response()->json([
                'catalogo' => view('listas.partials.catalogo-productos', [
                    'lista' => $lista,
                    'productos' => $productos,
                    'busqueda' => $busqueda,
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

    public function agregarProducto(StoreProductoListaRequest $request, Lista $lista): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $lista);

        $data = $request->validated();
        $productoId = (int) $data['id_producto'];
        $cantidad = (int) $data['cantidad'];
        $redirectDespensaId = isset($data['redirect_despensa_id']) ? (int) $data['redirect_despensa_id'] : null;
        $busqueda = trim((string) $request->input('q', ''));
        $pagina = max(1, (int) $request->input('page', 1));

        $productoEnLista = $lista->productos()
            ->where('productos.id', $productoId)
            ->first();
        $cantidadActual = (int) ($productoEnLista?->pivot?->cantidad ?? 0);
        $marcadoActual = (bool) ($productoEnLista?->pivot?->marcado ?? false);

        $lista->productos()->syncWithoutDetaching([
            $productoId => ['cantidad' => $cantidadActual + $cantidad, 'marcado' => $marcadoActual],
        ]);

        $mensaje = $cantidadActual > 0
            ? __('flash.listas.product_merged')
            : __('flash.listas.product_added');

        if ($request->expectsJson()) {
            $lista->load([
                'productos' => fn ($query) => $query->orderBy('nombre_producto'),
            ]);

            $productos = $this->obtenerCatalogoProductosParaLista($busqueda, $pagina);

            return response()->json([
                'status' => $mensaje,
                'catalogo' => view('listas.partials.catalogo-productos', [
                    'lista' => $lista,
                    'productos' => $productos,
                    'busqueda' => $busqueda,
                    'puedeEditar' => true,
                ])->render(),
                'listaHtml' => view('listas.partials.lista-productos-actual', [
                    'lista' => $lista,
                    'puedeEditar' => true,
                ])->render(),
                'resumenHtml' => view('listas.partials.resumen-productos', [
                    'lista' => $lista,
                    'puedeEditar' => true,
                ])->render(),
            ]);
        }

        if ($redirectDespensaId !== null && $redirectDespensaId > 0) {
            $despensa = Despensa::query()->findOrFail($redirectDespensaId);
            $this->authorize('view', $despensa);

            return redirect()
                ->route('despensas.stock', $despensa)
                ->with('status', $mensaje);
        }

        return redirect()
            ->route('listas.productos', array_filter([
                'lista' => $lista,
                'q' => $busqueda !== '' ? $busqueda : null,
                'page' => $pagina > 1 ? $pagina : null,
            ]))
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
            ->with('status', __('flash.listas.product_updated'));
    }

    public function quitarProducto(Lista $lista, Producto $producto): RedirectResponse
    {
        $this->authorize('update', $lista);

        $lista->productos()->detach($producto->id);

        return redirect()
            ->route('listas.productos', $lista)
            ->with('status', __('flash.listas.product_removed'));
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
                    ->withErrors(['id_despensa' => __('flash.listas.select_pantry')])
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
            ? __('flash.listas.finished_with_pantry')
            : __('flash.listas.finished_without_pantry');

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

        $seleccionActualToken = $this->obtenerTokenSeleccionActual($lista);

        return view('listas.recomendacion', compact('lista', 'ranking', 'comparativaAhorro', 'seleccionActualToken'));
    }

    public function elegirSupermercado(Request $request, Lista $lista, RecommendationService $recommendationService): RedirectResponse
    {
        $this->authorize('update', $lista);

        $data = $request->validate([
            'combinacion' => ['required', 'string', 'size:40'],
        ]);

        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        if ($usuario->latitud === null || $usuario->longitud === null) {
            throw ValidationException::withMessages([
                'ubicacion' => __('flash.listas.location_required'),
            ]);
        }

        $ranking = $recommendationService->recomendarSupermercados($lista, $usuario);
        $seleccion = collect($ranking)
            ->first(fn (array $fila): bool => (string) $fila['token'] === (string) $data['combinacion']);

        if ($seleccion === null) {
            throw ValidationException::withMessages([
                'combinacion' => __('flash.listas.recommendation_unavailable'),
            ]);
        }

        $supermercado = Supermercado::query()->findOrFail((int) $seleccion['id_super']);
        $lista->update([
            'id_supermercado_elegido' => $supermercado->id,
            'supermercados_recomendados_snapshot' => $seleccion['supermercados'],
        ]);

        $nombresSeleccionados = collect($seleccion['supermercados'])
            ->pluck('nombre_super')
            ->implode(', ');

        $mensaje = count($seleccion['supermercados']) > 1
            ? __('flash.listas.supermarkets_selected', ['names' => $nombresSeleccionados])
            : __('flash.listas.supermarket_selected', ['name' => $supermercado->nombre_super]);

        return redirect()
            ->route('listas.finalizar.confirmar', $lista)
            ->with('status', $mensaje);
    }

    private function obtenerTokenSeleccionActual(Lista $lista): ?string
    {
        $ids = collect($lista->supermercados_recomendados_snapshot ?? [])
            ->pluck('id_super')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($ids === [] && $lista->id_supermercado_elegido !== null) {
            $ids = [(int) $lista->id_supermercado_elegido];
        }

        if ($ids === []) {
            return null;
        }

        return sha1(implode('-', $ids));
    }

    private function aplicarBusquedaProducto(Builder $query, array $terminosBusqueda): void
    {
        foreach ($terminosBusqueda as $termino) {
            $query->where(function (Builder $subQuery) use ($termino): void {
                $subQuery
                    ->where('nombre_producto', 'like', '%'.$termino.'%')
                    ->orWhere('marca', 'like', '%'.$termino.'%')
                    ->orWhere('formato', 'like', '%'.$termino.'%');
            });
        }
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

    private function obtenerCatalogoProductosParaLista(string $busqueda, ?int $pagina = null): LengthAwarePaginator
    {
        $hayBusqueda = $busqueda !== '';
        $terminosBusqueda = $this->obtenerTerminosBusqueda($busqueda);

        if (! $hayBusqueda) {
            return $this->crearPaginadorCatalogoVacio();
        }

        return Producto::query()
            ->when($terminosBusqueda !== [], fn ($query) => $this->aplicarBusquedaProducto($query, $terminosBusqueda))
            ->when($terminosBusqueda !== [], fn ($query) => $this->aplicarOrdenBusquedaProducto($query, $busqueda, $terminosBusqueda))
            ->orderBy('nombre_producto')
            ->paginate(self::CATALOGO_PRODUCTOS_POR_PAGINA, ['*'], 'page', $pagina)
            ->withQueryString();
    }

    private function aplicarOrdenBusquedaProducto(Builder $query, string $busqueda, array $terminosBusqueda): void
    {
        $busquedaNormalizada = mb_strtolower(trim($busqueda));
        $primerTermino = mb_strtolower($terminosBusqueda[0] ?? '');
        $coincidenciasEnNombre = [];

        foreach ($terminosBusqueda as $termino) {
            $coincidenciasEnNombre[] = 'case when lower(nombre_producto) like ? then 1 else 0 end';
        }

        $query->orderByRaw(
            'case
                when lower(nombre_producto) like ? then 0
                when '.implode(' + ', $coincidenciasEnNombre).' = ? then 1
                when lower(nombre_producto) like ? then 2
                when lower(nombre_producto) like ? then 3
                when lower(marca) like ? then 4
                when lower(formato) like ? then 5
                else 6
            end',
            [
                $busquedaNormalizada.'%',
                ...array_map(
                    static fn (string $termino): string => '%'.mb_strtolower($termino).'%',
                    $terminosBusqueda
                ),
                count($terminosBusqueda),
                $primerTermino !== '' ? $primerTermino.'%' : $busquedaNormalizada.'%',
                $primerTermino !== '' ? '%'.$primerTermino.'%' : '%'.$busquedaNormalizada.'%',
                '%'.$busquedaNormalizada.'%',
                '%'.$busquedaNormalizada.'%',
            ]
        );

        if ($coincidenciasEnNombre !== []) {
            $query->orderByRaw(
                implode(' + ', $coincidenciasEnNombre).' desc',
                array_map(
                    static fn (string $termino): string => '%'.mb_strtolower($termino).'%',
                    $terminosBusqueda
                )
            );
        }
    }

    private function obtenerTerminosBusqueda(string $busqueda): array
    {
        $terminos = preg_split('/\s+/u', trim($busqueda)) ?: [];

        return array_values(array_filter($terminos, static fn (string $termino): bool => $termino !== ''));
    }

    private function crearPaginadorCatalogoVacio(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: self::CATALOGO_PRODUCTOS_POR_PAGINA,
            currentPage: 1,
            options: [
                'path' => request()->url(),
                'pageName' => 'page',
            ],
        );
    }
}
