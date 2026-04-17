@extends('layouts.app')

@section('title', 'Recomendacion | SmartSuper')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">SmartSuper</p>
            <h1>Recomendacion de supermercado</h1>
            <p class="hero-copy">Lista: {{ $lista->nombre_lista }}</p>
        </div>
        <a class="button button--ghost" href="{{ route('listas.index') }}">Volver a listas</a>
    </section>

    <section class="panel-card">
        @if (empty($ranking))
            <div class="empty-state">
                <h2>Sin recomendacion disponible</h2>
                <p>No hay datos suficientes para calcular el ranking.</p>
            </div>
        @else
            @foreach ($ranking as $item)
                <article class="list-row">
                    <div>
                        <h2>{{ $item['nombre_super'] }}</h2>
                        <p>Total cesta: {{ number_format((float) $item['total_cesta'], 2) }} EUR</p>
                        <p>Distancia: {{ number_format((float) $item['distancia_km'], 3) }} km</p>
                    </div>
                    <div class="row-actions">
                        <p><strong>Score: {{ number_format((float) $item['score'], 2) }} EUR</strong></p>
                    </div>
                </article>
            @endforeach
        @endif
    </section>
@endsection
