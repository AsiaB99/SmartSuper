@extends('layouts.app')

@section('title', 'Recomendación | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-5">
                <div>
                    <h1 class="ss-title text-left">Recomendación para {{ $lista->nombre_lista }}</h1>
                    <p class="mt-2 text-sm leading-7 text-ink-600">Ranking por coste de cesta y coste estimado por distancia.</p>
                </div>
                <a class="ss-btn-outline" href="{{ route('listas.index') }}">Volver a listas</a>
            </div>

            @if (count($ranking) === 0)
                <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                    <h2 class="text-xl font-semibold text-ink-900">Sin recomendación disponible</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">Verifica que la lista tenga productos, que existan precios en supermercados y que tu usuario tenga latitud/longitud.</p>
                </div>
            @else
                @if ($comparativaAhorro !== null)
                    <section class="mb-8 rounded-[15px] border-t-[5px] border-accent-500 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.08)]">
                        <p class="text-sm text-ink-600">Mejor opción:</p>
                        <h2 class="mt-1 text-3xl font-semibold text-ink-900">{{ $comparativaAhorro['mejor_super'] }}</h2>
                        <div class="mt-4 inline-flex rounded-[10px] bg-[#dff9fb] px-4 py-3 text-sm font-semibold text-brand-600">
                            Ahorras {{ number_format((float) $comparativaAhorro['ahorro_absoluto'], 2, ',', '.') }} € frente a {{ $comparativaAhorro['segunda_super'] }}
                        </div>
                    </section>
                @endif

                <section class="grid gap-5">
                    @foreach ($ranking as $fila)
                        <article class="overflow-hidden rounded-[15px] bg-white shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1">
                            <div class="grid gap-4 p-5 md:grid-cols-[1fr_repeat(4,auto)] md:items-center">
                                <div>
                                    <h2 class="text-xl font-semibold text-ink-900">{{ $fila['nombre_super'] }}</h2>
                                    <p class="mt-1 text-sm text-ink-500">{{ $fila['items_cesta'] }} productos en cesta</p>
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">Total cesta</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['total_cesta'], 2, ',', '.') }} €</p>
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">Distancia</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['distancia_km'], 3, ',', '.') }} km</p>
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">Coste distancia</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['coste_distancia'], 2, ',', '.') }} €</p>
                                </div>
                                <div class="rounded-[10px] bg-brand-50 px-4 py-3 text-right">
                                    <p class="text-xs font-semibold uppercase text-brand-600">Score final</p>
                                    <p class="text-2xl font-bold text-brand-600">{{ number_format((float) $fila['score'], 2, ',', '.') }} €</p>
                                </div>
                            </div>

                            <details class="border-t border-[#eee] bg-[#f9f9f9] p-5" @if ($loop->first) open @endif>
                                <summary class="cursor-pointer list-none text-sm font-semibold text-brand-600">Ver desglose de cesta</summary>
                                <div class="mt-4 overflow-hidden rounded-[10px] border border-[#eee] bg-white">
                                    <table class="min-w-full divide-y divide-[#eee] text-sm text-ink-700">
                                        <thead class="bg-brand-50 text-left text-xs font-semibold uppercase text-ink-600">
                                            <tr>
                                                <th class="px-4 py-3">Producto</th>
                                                <th class="px-4 py-3">Cantidad</th>
                                                <th class="px-4 py-3">Precio unitario</th>
                                                <th class="px-4 py-3">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[#eee]">
                                            @foreach ($fila['detalle_cesta'] as $detalle)
                                                <tr>
                                                    <td class="px-4 py-3">{{ $detalle['nombre_producto'] }}</td>
                                                    <td class="px-4 py-3">{{ $detalle['cantidad'] }}</td>
                                                    <td class="px-4 py-3">{{ number_format((float) $detalle['precio_unitario'], 2, ',', '.') }} €</td>
                                                    <td class="px-4 py-3 font-semibold text-ink-900">{{ number_format((float) $detalle['subtotal'], 2, ',', '.') }} €</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </article>
                    @endforeach
                </section>
            @endif
        </div>
    </section>
@endsection
