<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupermercadoRequest;
use App\Http\Requests\UpdateSupermercadoRequest;
use App\Models\Supermercado;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SupermercadoController extends Controller
{
    public function index(): View
    {
        $supermercados = Supermercado::orderBy('nombre_super')->paginate(15);

        return view('supermercados.index', compact('supermercados'));
    }

    public function create(): View
    {
        return view('supermercados.create');
    }

    public function store(StoreSupermercadoRequest $request): RedirectResponse
    {
        Supermercado::create($request->validated());

        return redirect()
            ->route('supermercados.index')
            ->with('status', 'Supermercado creado exitosamente.');
    }

    public function edit(Supermercado $supermercado): View
    {
        return view('supermercados.edit', compact('supermercado'));
    }

    public function update(UpdateSupermercadoRequest $request, Supermercado $supermercado): RedirectResponse
    {
        $supermercado->update($request->validated());

        return redirect()
            ->route('supermercados.index')
            ->with('status', 'Supermercado actualizado exitosamente.');
    }

    public function destroy(Supermercado $supermercado): RedirectResponse
    {
        $supermercado->delete();

        return redirect()
            ->route('supermercados.index')
            ->with('status', 'Supermercado eliminado exitosamente.');
    }
}
