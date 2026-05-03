@extends('layouts.app')

@section('title', 'Productos | Admin')

@section('content')
    @php($esAdmin = auth()->user()?->isAdmin() ?? false)

    <section class="hero-card">
        <div>
            <p class="eyebrow">{{ $esAdmin ? 'Admin' : 'Catalogo' }}</p>
            <h1>Productos</h1>
            <p class="hero-copy">{{ $esAdmin ? 'Gestiona el catálogo de productos disponibles.' : 'Consulta los productos disponibles en el catálogo.' }}</p>
        </div>
        <div class="row-actions">
            <a class="button button--ghost" href="{{ route('precios.index') }}">Ver precios</a>
            @if ($esAdmin)
                <a class="button button--primary" href="{{ route('admin.productos.create') }}">Nuevo producto</a>
            @endif
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
                @if ($esAdmin)
                    <div class="row-actions">
                        <a class="button button--ghost" href="{{ route('admin.productos.edit', $producto) }}">Editar</a>
                        <form action="{{ route('admin.productos.destroy', $producto) }}" method="POST" onsubmit="return confirm('¿Eliminar este producto?');">
                            @csrf
                            @method('DELETE')
                            <button class="button button--danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                @endif
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
