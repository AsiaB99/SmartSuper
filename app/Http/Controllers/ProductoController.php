<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => 'productos']);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => 'productos']);
    }

    public function store(StoreProductoRequest $request): RedirectResponse
    {
        Producto::create($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', __('flash.productos.created'));
    }

    public function edit(Producto $producto): RedirectResponse
    {
        return redirect()->route('admin.index', ['tab' => 'productos']);
    }

    public function update(UpdateProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()
            ->route('productos.index')
            ->with('status', __('flash.productos.updated'));
    }

    public function destroy(Request $request, Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()
            ->route('admin.index', array_filter([
                'tab' => 'productos',
                'productos_busqueda' => trim((string) $request->query('productos_busqueda', '')),
                'productos_page' => $request->query('productos_page'),
            ], static fn ($value): bool => $value !== null && $value !== ''))
            ->with('status', __('flash.productos.deleted'));
    }
}
