@extends('layouts.app')

@section('title', 'Productos de lista | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Productos de la lista</h1>
            <p class="hero-copy">Lista: {{ $lista->nombre_lista }}</p>
        </div>
        <a class="button button--ghost" href="{{ route('listas.index') }}">Volver</a>
    </section>

    <section class="panel-card">
        <form class="toolbar-search" action="{{ route('listas.productos', $lista) }}" method="GET">
            <label class="field">
                <span>Buscar producto</span>
                <input type="search" name="q" value="{{ $busqueda }}" placeholder="Ej: arroz, pasta, leche">
            </label>
            <button class="button button--ghost" type="submit">Buscar</button>
            @if ($busqueda !== '')
                <a class="button button--ghost" href="{{ route('listas.productos', $lista) }}">Limpiar</a>
            @endif
        </form>
    </section>

    @if ($puedeEditar)
        <section class="panel-card form-card">
            <div class="section-heading">
                <p class="eyebrow">Añadir producto</p>
                <h2>Agregar a lista</h2>
            </div>

            <form class="stack-form" action="{{ route('listas.productos.agregar', $lista) }}" method="POST">
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
                        <span>Cantidad</span>
                        <input type="number" name="cantidad" min="1" step="1" value="{{ old('cantidad', 1) }}" required>
                        @error('cantidad')<small>{{ $message }}</small>@enderror
                    </label>
                </div>

                <button class="button button--primary" type="submit">Añadir a la lista</button>
            </form>
        </section>
    @else
        <section class="panel-card">
            <p class="tag-muted">Modo solo lectura. Permiso viewer: puedes consultar la lista, no editar.</p>
        </section>
    @endif

    <section class="panel-card">
        <div class="section-heading">
            <p class="eyebrow">Contenido</p>
            <h2>Productos en lista</h2>
        </div>

        @forelse ($lista->productos as $producto)
            <article class="list-row">
                <div>
                    <h3>{{ $producto->nombre_producto }}</h3>
                    <p>Cantidad: {{ (int) $producto->pivot->cantidad }}</p>
                    <p>Marcado: {{ $producto->pivot->marcado ? 'Sí' : 'No' }}</p>
                </div>

                <div class="row-actions">
                    @if ($puedeEditar)
                        <form action="{{ route('listas.productos.actualizar', [$lista, $producto]) }}" method="POST" class="row-actions">
                            @csrf
                            @method('PATCH')
                            <input type="number" name="cantidad" min="1" step="1" value="{{ (int) $producto->pivot->cantidad }}" required>
                            <label>
                                <input type="checkbox" name="marcado" value="1" @checked($producto->pivot->marcado)>
                                Marcado
                            </label>
                            <button class="button button--ghost" type="submit">Guardar</button>
                        </form>

                        <form action="{{ route('listas.productos.quitar', [$lista, $producto]) }}" method="POST">
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
                <p>Añade productos para empezar la lista de compra.</p>
            </div>
        @endforelse
    </section>
@endsection
