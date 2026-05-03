@extends('layouts.app')

@section('title', 'Precios | Admin')

@section('content')
    @php($esAdmin = auth()->user()?->isAdmin() ?? false)

    <section class="hero-card">
        <div>
            <p class="eyebrow">{{ $esAdmin ? 'Admin' : 'Catalogo' }}</p>
            <h1>Precios por supermercado</h1>
            <p class="hero-copy">{{ $esAdmin ? 'Gestiona precios de producto en cada supermercado para mejorar recomendaciones.' : 'Consulta precios de producto en cada supermercado.' }}</p>
        </div>
        @if ($esAdmin)
            <a class="button button--primary" href="{{ route('admin.precios.create') }}">Nuevo precio</a>
        @endif
    </section>

    <section class="panel-card">
        @forelse ($precios as $precio)
            <article class="list-row">
                <div>
                    <h2>{{ $precio->nombre_producto }}</h2>
                    <p>Supermercado: <strong>{{ $precio->nombre_super }}</strong></p>
                    <p>
                        Precio: <strong>${{ number_format((float) $precio->precio, 2, ',', '.') }}</strong>
                        @if ($precio->precio_unidad !== null)
                            | Unidad: ${{ number_format((float) $precio->precio_unidad, 2, ',', '.') }}
                            {{ $precio->unidad_ref ?? '' }}
                        @endif
                    </p>
                </div>
                @if ($esAdmin)
                    <div class="row-actions">
                        <a class="button button--ghost" href="{{ route('admin.precios.edit', [$precio->id_producto, $precio->id_super]) }}">Editar</a>
                        <form action="{{ route('admin.precios.destroy', [$precio->id_producto, $precio->id_super]) }}" method="POST" onsubmit="return confirm('¿Eliminar este precio?');">
                            @csrf
                            @method('DELETE')
                            <button class="button button--danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="empty-state">
                <h2>No hay precios cargados</h2>
                <p>Agrega precios para activar ranking real de supermercados.</p>
            </div>
        @endforelse
    </section>

    <div class="pagination">
        {{ $precios->links() }}
    </div>
@endsection
