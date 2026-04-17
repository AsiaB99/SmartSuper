@extends('layouts.app')

@section('title', 'Despensas | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Despensas</h1>
            <p class="hero-copy">Gestiona tus despensas base antes de sumar flujo de stock y movimientos.</p>
        </div>
        <a class="button button--primary" href="{{ route('despensas.create') }}">Nueva despensa</a>
    </section>

    <section class="panel-card">
        @forelse ($despensas as $despensa)
            <article class="list-row">
                <div>
                    <h2>{{ $despensa->nombre_despensa }}</h2>
                    <p>Creada: {{ optional($despensa->fecha_creacion)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                </div>
                <div class="row-actions">
                    <a class="button button--ghost" href="{{ route('despensas.stock', $despensa) }}">Stock</a>
                    <a class="button button--ghost" href="{{ route('despensas.edit', $despensa) }}">Editar</a>
                    <form action="{{ route('despensas.destroy', $despensa) }}" method="POST" onsubmit="return confirm('¿Eliminar esta despensa?');">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h2>No hay despensas todavía</h2>
                <p>Crea la primera despensa para empezar a gestionar stock.</p>
            </div>
        @endforelse
    </section>
@endsection
