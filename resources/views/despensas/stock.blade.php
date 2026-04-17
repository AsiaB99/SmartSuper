@extends('layouts.app')

@section('title', 'Stock despensa | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Stock de despensa</h1>
            <p class="hero-copy">Despensa: {{ $despensa->nombre_despensa }}</p>
        </div>
        <a class="button button--ghost" href="{{ route('despensas.index') }}">Volver</a>
    </section>

    <section class="panel-card">
        <form class="toolbar-search" action="{{ route('despensas.stock', $despensa) }}" method="GET">
            <label class="field">
                <span>Buscar producto</span>
                <input type="search" name="q" value="{{ $busqueda }}" placeholder="Ej: arroz, pasta, leche">
            </label>
            <button class="button button--ghost" type="submit">Buscar</button>
            @if ($busqueda !== '')
                <a class="button button--ghost" href="{{ route('despensas.stock', $despensa) }}">Limpiar</a>
            @endif
        </form>
    </section>

    @if ($puedeEditar)
        <section class="panel-card form-card">
            <div class="section-heading">
                <p class="eyebrow">Añadir producto</p>
                <h2>Incrementar stock</h2>
            </div>

            <form class="stack-form" action="{{ route('despensas.stock.agregar', $despensa) }}" method="POST">
                @csrf
                <div class="field-grid">
                    <label class="field">
                        <span>Producto</span>
                        <select name="id_producto" required>
                            <option value="">Selecciona producto</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}" @selected((int) old('id_producto') === $producto->id)>
                                    {{ $producto->nombre_producto }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_producto')<small>{{ $message }}</small>@enderror
                    </label>

                    <label class="field">
                        <span>Cantidad a añadir</span>
                        <input type="number" name="stock" min="1" step="1" value="{{ old('stock', 1) }}" required>
                        @error('stock')<small>{{ $message }}</small>@enderror
                    </label>
                </div>

                <button class="button button--primary" type="submit">Añadir al stock</button>
            </form>
        </section>
    @else
        <section class="panel-card">
            <p class="tag-muted">Modo solo lectura. Permiso viewer: puedes consultar stock, no editar.</p>
        </section>
    @endif

    <section class="panel-card">
        <div class="section-heading">
            <p class="eyebrow">Inventario</p>
            <h2>Productos en despensa</h2>
        </div>

        @forelse ($despensa->productos as $producto)
            <article class="list-row">
                <div>
                    <h3>{{ $producto->nombre_producto }}</h3>
                    <p>Stock actual: {{ (int) $producto->pivot->stock }}</p>
                </div>

                <div class="row-actions">
                    @if ($puedeEditar)
                        <form action="{{ route('despensas.stock.actualizar', [$despensa, $producto]) }}" method="POST" class="row-actions">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="stock" min="0" step="1" value="{{ (int) $producto->pivot->stock }}" required>
                            <button class="button button--ghost" type="submit">Ajustar</button>
                        </form>

                        <form action="{{ route('despensas.stock.quitar', [$despensa, $producto]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button class="button button--danger" type="submit">Quitar</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state">
                <h3>Sin productos</h3>
                <p>Añade productos para empezar control de stock.</p>
            </div>
        @endforelse
    </section>
@endsection
