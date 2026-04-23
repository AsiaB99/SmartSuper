@extends('layouts.app')

@section('title', 'Recomendacion | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Recomendacion para {{ $lista->nombre_lista }}</h1>
            <p class="hero-copy">Ranking por score total: coste de cesta + coste estimado por distancia.</p>
        </div>
        <a class="button button--ghost" href="{{ route('listas.index') }}">Volver a listas</a>
    </section>

    <section class="panel-card">
        @if (count($ranking) === 0)
            <div class="empty-state">
                <h2>Sin recomendacion disponible</h2>
                <p>Verifica que la lista tenga productos, que existan precios en supermercados y que tu usuario tenga latitud/longitud.</p>
            </div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Supermercado</th>
                        <th>Total cesta</th>
                        <th>Distancia (km)</th>
                        <th>Coste distancia</th>
                        <th>Score final</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ranking as $fila)
                        <tr>
                            <td>{{ $fila['nombre_super'] }}</td>
                            <td>{{ number_format((float) $fila['total_cesta'], 2, ',', '.') }} EUR</td>
                            <td>{{ number_format((float) $fila['distancia_km'], 3, ',', '.') }}</td>
                            <td>{{ number_format((float) $fila['coste_distancia'], 2, ',', '.') }} EUR</td>
                            <td><strong>{{ number_format((float) $fila['score'], 2, ',', '.') }} EUR</strong></td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <details class="details-card" @if ($loop->first) open @endif>
                                    <summary>Ver desglose de cesta ({{ $fila['items_cesta'] }} productos)</summary>
                                    <table class="table table--compact">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                                <th>Precio unitario</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($fila['detalle_cesta'] as $detalle)
                                                <tr>
                                                    <td>{{ $detalle['nombre_producto'] }}</td>
                                                    <td>{{ $detalle['cantidad'] }}</td>
                                                    <td>{{ number_format((float) $detalle['precio_unitario'], 2, ',', '.') }} EUR</td>
                                                    <td>{{ number_format((float) $detalle['subtotal'], 2, ',', '.') }} EUR</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
