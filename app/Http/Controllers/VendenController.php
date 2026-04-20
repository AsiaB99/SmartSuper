<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendenRequest;
use App\Http\Requests\UpdateVendenRequest;
use App\Models\Producto;
use App\Models\Supermercado;
use App\Models\Venden;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class VendenController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->isAdmin()) {
                abort(403, 'Solo administradores pueden gestionar precios.');
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        $precios = DB::table('venden as v')
            ->join('productos as p', 'p.id', '=', 'v.id_producto')
            ->join('supermercados as s', 's.id', '=', 'v.id_super')
            ->select([
                'v.id_producto',
                'v.id_super',
                'v.precio',
                'v.precio_unidad',
                'v.unidad_ref',
                'v.fecha_actualizacion',
                'p.nombre_producto',
                's.nombre_super',
            ])
            ->orderByDesc('v.fecha_actualizacion')
            ->paginate(20);

        return view('precios.index', compact('precios'));
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
