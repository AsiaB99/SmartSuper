<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDespensaRequest;
use App\Http\Requests\UpdateDespensaRequest;
use App\Models\Despensa;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
            ->with('status', 'Despensa creada correctamente.');
    }

    public function edit(Despensa $despensa): View
    {
        return view('despensas.edit', compact('despensa'));
    }

    public function update(UpdateDespensaRequest $request, Despensa $despensa): RedirectResponse
    {
        $despensa->update($request->validated());

        return redirect()
            ->route('despensas.index')
            ->with('status', 'Despensa actualizada correctamente.');
    }

    public function destroy(Despensa $despensa): RedirectResponse
    {
        $despensa->delete();

        return redirect()
            ->route('despensas.index')
            ->with('status', 'Despensa eliminada correctamente.');
    }
}