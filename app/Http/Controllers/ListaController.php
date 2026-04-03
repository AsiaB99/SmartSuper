<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListaRequest;
use App\Http\Requests\UpdateListaRequest;
use App\Models\Lista;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ListaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Lista::class, 'lista');
    }

    public function index(): View
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = auth()->user();

        $listas = Lista::query()
            ->when(! $usuario->isAdmin(), function ($query) use ($usuario) {
                $query->whereHas('usuarios', function ($subQuery) use ($usuario) {
                    $subQuery->where('users.id', $usuario->id);
                });
            })
            ->orderByDesc('fecha_creacion')
            ->get();

        return view('listas.index', compact('listas'));
    }

    public function create(): View
    {
        return view('listas.create');
    }

    public function store(StoreListaRequest $request): RedirectResponse
    {
        /** @var \App\Models\User&Authenticatable $usuario */
        $usuario = $request->user();

        $lista = Lista::create($request->validated());
        $lista->usuarios()->attach($usuario->id, ['permiso_lista' => 'owner']);

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista creada correctamente.');
    }

    public function edit(Lista $lista): View
    {
        return view('listas.edit', compact('lista'));
    }

    public function update(UpdateListaRequest $request, Lista $lista): RedirectResponse
    {
        $lista->update($request->validated());

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista actualizada correctamente.');
    }

    public function destroy(Lista $lista): RedirectResponse
    {
        $lista->delete();

        return redirect()
            ->route('listas.index')
            ->with('status', 'Lista eliminada correctamente.');
    }
}
