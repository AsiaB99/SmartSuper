@extends('layouts.app')

@section('title', 'Listas | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Listas de compra</h1>
            <p class="hero-copy">Gestiona tus listas base antes de sumar permisos, productos y recomendaciones de supermercado.</p>
        </div>
        <a class="button button--primary" href="{{ route('listas.create') }}">Nueva lista</a>
    </section>

    <section class="panel-card">
        @forelse ($listas as $lista)
            <article class="list-row">
                <div>
                    <h2>{{ $lista->nombre_lista }}</h2>
                    <p>Estado: <strong>{{ $lista->estado }}</strong></p>
                    <p>Creada: {{ optional($lista->fecha_creacion)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                </div>
                <div class="row-actions">
                    <form action="{{ route('listas.finalizar', $lista) }}" method="POST">
                        @csrf
                        <button class="button button--primary" type="submit">Finalizar lista</button>
                    </form>
                    <a class="button" href="{{ route('listas.recomendacion', $lista) }}">Recomendar super</a>
                    <a class="button button--ghost" href="{{ route('listas.edit', $lista) }}">Editar</a>
                    <form action="{{ route('listas.destroy', $lista) }}" method="POST" onsubmit="return confirm('¿Eliminar esta lista?');">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h2>No hay listas todavía</h2>
                <p>Crea la primera lista para empezar a estructurar el flujo de compra.</p>
            </div>
        @endforelse
    </section>
@endsection
