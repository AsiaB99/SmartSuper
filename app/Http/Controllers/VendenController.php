<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendenRequest;
use App\Http\Requests\UpdateVendenRequest;
use App\Models\Producto;
use App\Models\Supermercado;
use App\Models\Venden;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendenController extends Controller
{
    private const PRODUCTOS_POR_PAGINA = 6;
    private const MAX_BUSQUEDA_LEN = 120;

    public function index(Request $request): View|JsonResponse
    {
        $busqueda = mb_substr(trim((string) $request->string('busqueda')), 0, self::MAX_BUSQUEDA_LEN);
        $hayBusqueda = $busqueda !== '';
        $productos = $hayBusqueda
            ? $this->obtenerProductosComparables($busqueda)
            : $this->crearPaginadorVacio();

        $productoId = $request->integer('producto');

        if ($productoId === 0) {
            $productoId = null;
        }

        if (! $hayBusqueda) {
            $productoId = null;
        }

        if ($hayBusqueda && $productoId === null && $productos->isNotEmpty()) {
            $productoId = (int) $productos->first()->id;
        }

        $productoSeleccionado = $hayBusqueda && $productoId !== null
            ? Producto::query()->find($productoId, ['id', 'nombre_producto', 'marca', 'formato', 'imagen'])
            : null;

        $precios = $productoSeleccionado !== null
            ? $this->obtenerPreciosPorCadena((int) $productoSeleccionado->id)
            : collect();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'busqueda' => $busqueda,
                'page' => $productos->currentPage(),
                'productoId' => $productoId,
                'productosHtml' => view('precios.partials.productos', [
                    'busqueda' => $busqueda,
                    'productoId' => $productoId,
                    'productos' => $productos,
                ])->render(),
                'comparadorHtml' => view('precios.partials.comparador', [
                    'productoSeleccionado' => $productoSeleccionado,
                    'precios' => $precios,
                    'mejorPrecio' => $precios->min('precio'),
                ])->render(),
            ]);
        }

        return view('precios.index', [
            'busqueda' => $busqueda,
            'productoId' => $productoId,
            'productoSeleccionado' => $productoSeleccionado,
            'productos' => $productos,
            'precios' => $precios,
            'mejorPrecio' => $precios->min('precio'),
        ]);
    }

    private function obtenerProductosComparables(string $busqueda): LengthAwarePaginator
    {
        $mejorCadenaNombreSubquery = $this->subconsultaMejorCadenaPorProducto('nombre_super');
        $mejorCadenaPrecioSubquery = $this->subconsultaMejorCadenaPorProducto('precio');
        $terminosBusqueda = $this->obtenerTerminosBusqueda($busqueda);

        return Producto::query()
            ->where(function ($query): void {
                $query->whereExists(function ($subQuery): void {
                    $subQuery->select(DB::raw(1))
                        ->from('venden')
                        ->whereColumn('venden.id_producto', 'productos.id');
                })->orWhereExists(function ($subQuery): void {
                    $subQuery->select(DB::raw(1))
                        ->from('precios_cadena')
                        ->whereColumn('precios_cadena.id_producto', 'productos.id');
                });
            })
            ->when($terminosBusqueda !== [], function ($query) use ($busqueda, $terminosBusqueda): void {
                $this->aplicarBusquedaProducto($query, $terminosBusqueda);
                $this->aplicarOrdenBusquedaProducto($query, $busqueda, $terminosBusqueda);
            })
            ->addSelect([
                'mejor_cadena_nombre' => $mejorCadenaNombreSubquery,
                'mejor_cadena_precio' => $mejorCadenaPrecioSubquery,
            ])
            ->orderBy('nombre_producto')
            ->paginate(self::PRODUCTOS_POR_PAGINA)
            ->withQueryString();
    }

    private function crearPaginadorVacio(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: self::PRODUCTOS_POR_PAGINA,
            currentPage: 1,
            options: [
                'path' => route('precios.index'),
                'pageName' => 'page',
            ],
        );
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

    private function obtenerPreciosPorCadena(int $productoId)
    {
        return DB::table('cadenas_supermercados as cs')
            ->leftJoin('precios_cadena as pc', function ($join) use ($productoId): void {
                $join->on('cs.id', '=', 'pc.id_cadena')
                    ->where('pc.id_producto', '=', $productoId);
            })
            ->leftJoin('venden as v', function ($join) use ($productoId): void {
                $join->where('v.id_producto', '=', $productoId);
            })
            ->leftJoin('supermercados as s', function ($join): void {
                $join->on('s.id', '=', 'v.id_super')
                    ->on('s.id_cadena', '=', 'cs.id')
                    ->where('s.activo', true);
            })
            ->groupBy('cs.id', 'cs.nombre')
            ->select([
                'cs.id as id_cadena',
                DB::raw('cs.nombre as nombre_super'),
                DB::raw('NULL as direccion'),
                DB::raw('COALESCE(MAX(pc.precio), MIN(v.precio)) as precio'),
                DB::raw('COALESCE(MAX(pc.precio_unidad), MIN(v.precio_unidad)) as precio_unidad'),
                DB::raw('COALESCE(MAX(pc.unidad_ref), MIN(v.unidad_ref)) as unidad_ref'),
                DB::raw('COALESCE(MAX(pc.fecha_actualizacion), MAX(v.fecha_actualizacion)) as fecha_actualizacion'),
            ])
            ->havingRaw('COALESCE(MAX(pc.precio), MIN(v.precio)) IS NOT NULL')
            ->orderByRaw('COALESCE(MAX(pc.precio), MIN(v.precio)) ASC')
            ->get();
    }

    private function subconsultaMejorCadenaPorProducto(string $columna): QueryBuilder
    {
        $preciosPorCadena = DB::query()
            ->fromSub($this->subconsultaPreciosPorProductoYCadena(), 'precios_cadena_producto')
            ->select("precios_cadena_producto.{$columna}")
            ->whereColumn('precios_cadena_producto.id_producto', 'productos.id')
            ->orderBy('precios_cadena_producto.precio')
            ->orderBy('precios_cadena_producto.nombre_super')
            ->limit(1);

        return $preciosPorCadena;
    }

    private function subconsultaPreciosPorProductoYCadena(): QueryBuilder
    {
        $preciosCadena = DB::table('precios_cadena as pc')
            ->join('cadenas_supermercados as cs', 'cs.id', '=', 'pc.id_cadena')
            ->select([
                'pc.id_producto',
                'cs.nombre as nombre_super',
                'pc.precio',
            ]);

        $preciosTiendas = DB::table('venden as v')
            ->join('supermercados as s', function ($join): void {
                $join->on('s.id', '=', 'v.id_super')
                    ->where('s.activo', true)
                    ->whereNotNull('s.id_cadena');
            })
            ->join('cadenas_supermercados as cs', 'cs.id', '=', 's.id_cadena')
            ->groupBy('v.id_producto', 'cs.id', 'cs.nombre')
            ->select([
                'v.id_producto',
                'cs.nombre as nombre_super',
                DB::raw('MIN(v.precio) as precio'),
            ]);

        return $preciosCadena->unionAll($preciosTiendas);
    }

    public function create(): View
    {
        $productos = Producto::query()
            ->orderBy('nombre_producto')
            ->get(['id', 'nombre_producto']);

        $supermercados = Supermercado::query()
            ->orderBy('nombre_super')
            ->get(['id', 'nombre_super']);

        return view('precios.create', compact('productos', 'supermercados'));
    }

    public function store(StoreVendenRequest $request): RedirectResponse
    {
        Venden::query()->create($request->validated());

        return redirect()
            ->route('precios.index')
            ->with('status', __('flash.precios.created'));
    }

    public function edit(int $producto, int $supermercado): View
    {
        $precio = Venden::query()
            ->where('id_producto', $producto)
            ->where('id_super', $supermercado)
            ->firstOrFail();

        return view('precios.edit', compact('precio'));
    }

    public function update(UpdateVendenRequest $request, int $producto, int $supermercado): RedirectResponse
    {
        Venden::query()
            ->where('id_producto', $producto)
            ->where('id_super', $supermercado)
            ->update($request->validated());

        return redirect()
            ->route('precios.index')
            ->with('status', __('flash.precios.updated'));
    }

    public function destroy(int $producto, int $supermercado): RedirectResponse
    {
        Venden::query()
            ->where('id_producto', $producto)
            ->where('id_super', $supermercado)
            ->delete();

        return redirect()
            ->route('precios.index')
            ->with('status', __('flash.precios.deleted'));
    }
}
