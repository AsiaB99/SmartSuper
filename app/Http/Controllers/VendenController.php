<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendenRequest;
use App\Http\Requests\UpdateVendenRequest;
use App\Models\Producto;
use App\Models\Supermercado;
use App\Models\Venden;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendenController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->string('busqueda'));

        $productos = Producto::query()
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('venden')
                    ->whereColumn('venden.id_producto', 'productos.id');
            })
            ->when($busqueda !== '', function ($query) use ($busqueda): void {
                $query->where('nombre_producto', 'like', "%{$busqueda}%");
            })
            ->orderBy('nombre_producto')
            ->limit(8)
            ->get(['id', 'nombre_producto', 'marca', 'formato']);

        $productoId = $request->integer('producto');

        if ($productoId === 0) {
            $productoId = null;
        }

        if ($productoId === null && $productos->isNotEmpty()) {
            $productoId = (int) $productos->first()->id;
        }

        $precios = collect();
        $productoSeleccionado = null;

        if ($productoId !== null) {
            $productoSeleccionado = Producto::query()
                ->find($productoId, ['id', 'nombre_producto', 'marca', 'formato']);

            if ($productoSeleccionado !== null) {
                $precios = DB::table('venden as v')
                    ->join('supermercados as s', 's.id', '=', 'v.id_super')
                    ->where('v.id_producto', $productoId)
                    ->select([
                        'v.id_producto',
                        'v.id_super',
                        'v.precio',
                        'v.precio_unidad',
                        'v.unidad_ref',
                        'v.fecha_actualizacion',
                        's.nombre_super',
                        's.direccion',
                    ])
                    ->orderBy('v.precio')
                    ->get();
            }
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
            ->with('status', 'Precio creado correctamente.');
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
            ->with('status', 'Precio actualizado correctamente.');
    }

    public function destroy(int $producto, int $supermercado): RedirectResponse
    {
        Venden::query()
            ->where('id_producto', $producto)
            ->where('id_super', $supermercado)
            ->delete();

        return redirect()
            ->route('precios.index')
            ->with('status', 'Precio eliminado correctamente.');
    }
}
