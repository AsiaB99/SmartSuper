@extends('layouts.app')

@section('title', 'Productos | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Productos</h1>
            <p class="hero-copy">Gestiona el catálogo de productos disponibles.</p>
        </div>
        <div class="row-actions">
            <a class="button button--ghost" href="{{ route('precios.index') }}">Ver precios</a>
            <a class="button button--primary" href="{{ route('productos.create') }}">Nuevo producto</a>
        </div>
    </section>

    @if (session('status'))
        <div class="alert alert--success">
            {{ session('status') }}
        </div>
    @endif

    <section class="panel-card">
        @forelse ($productos as $producto)
            <article class="list-row">
                <div>
                    <h2>{{ $producto->nombre_producto }}</h2>
                    <p>Sección: <strong>{{ $producto->seccion->nombre_seccion }}</strong></p>
                </div>
                <div class="row-actions">
                    <a class="button button--ghost" href="{{ route('productos.edit', $producto) }}">Editar</a>
                    <form action="{{ route('productos.destroy', $producto) }}" method="POST" onsubmit="return confirm('¿Eliminar este producto?');">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h2>No hay productos</h2>
                <p>Crea el primero para empezar a armar el catálogo.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination">
        {{ $productos->links() }}
    </div>
@endsection
