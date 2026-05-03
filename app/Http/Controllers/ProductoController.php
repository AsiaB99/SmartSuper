<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use App\Models\Seccion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ProductoController extends Controller
{
    public function index(): View
    {
        $productos = Producto::with('seccion')->orderBy('nombre_producto')->paginate(15);
        $secciones = Seccion::orderBy('nombre_seccion')->get();

        return view('productos.index', compact('productos', 'secciones'));
    }

    public function create(): View
    {
        $secciones = Seccion::orderBy('nombre_seccion')->get();

        return view('productos.create', compact('secciones'));
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        Producto::create($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', 'Producto creado exitosamente.');
    }

    public function edit(Producto $producto): View
    {
        $secciones = Seccion::orderBy('nombre_seccion')->get();

        return view('productos.edit', compact('producto', 'secciones'));
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', 'Producto actualizado exitosamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()
            ->route('productos.index')
            ->with('status', 'Producto eliminado exitosamente.');
    }
}
