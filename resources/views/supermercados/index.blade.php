@extends('layouts.app')

@section('title', 'Supermercados | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Supermercados</h1>
            <p class="hero-copy">Gestiona la red de supermercados disponibles en el catálogo.</p>
        </div>
        <div class="row-actions">
            <a class="button button--ghost" href="{{ route('precios.index') }}">Ver precios</a>
            <a class="button button--primary" href="{{ route('supermercados.create') }}">Nuevo supermercado</a>
        </div>
    </section>

    @if (session('status'))
        <div class="alert alert--success">
            {{ session('status') }}
        </div>
    @endif

    <section class="panel-card">
        @forelse ($supermercados as $supermercado)
            <article class="list-row">
                <div>
                    <h2>{{ $supermercado->nombre_super }}</h2>
                    <p>Latitud: {{ $supermercado->latitud ?? 'No definida' }}</p>
                    <p>Longitud: {{ $supermercado->longitud ?? 'No definida' }}</p>
                </div>
                <div class="row-actions">
                    <a class="button button--ghost" href="{{ route('supermercados.edit', $supermercado) }}">Editar</a>
                    <form action="{{ route('supermercados.destroy', $supermercado) }}" method="POST" onsubmit="return confirm('¿Eliminar este supermercado?');">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h2>No hay supermercados</h2>
                <p>Crea el primero para empezar a armar el catálogo.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination">
        {{ $supermercados->links() }}
    </div>
@endsection
