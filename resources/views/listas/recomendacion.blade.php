@extends('layouts.app')

@section('title', __('listas.recommendation.meta_title'))

@section('content')
    <section class="ss-section">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-5">
                <div>
                    <h1 class="ss-title text-left">{{ __('listas.recommendation.title', ['name' => $lista->nombre_lista]) }}</h1>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('listas.recommendation.subtitle') }}</p>
                </div>
                <a class="ss-btn-outline" href="{{ route('listas.index') }}">{{ __('listas.recommendation.back') }}</a>
            </div>

            @if (count($ranking) === 0)
                <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                    <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.recommendation.empty.title') }}</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('listas.recommendation.empty.text') }}</p>
                </div>
            @else
                @if ($comparativaAhorro !== null)
                    <section class="mb-8 rounded-[15px] border-t-[5px] border-accent-500 bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.08)]">
                        <p class="text-sm text-ink-600">{{ __('listas.recommendation.best_option') }}</p>
                        <h2 class="mt-1 text-3xl font-semibold text-ink-900">{{ $comparativaAhorro['mejor_super'] }}</h2>
                        <div class="mt-4 inline-flex rounded-[10px] bg-[var(--color-info-suave)] px-4 py-3 text-sm font-semibold text-brand-600">
                            {{ __('listas.recommendation.saving', ['amount' => number_format((float) $comparativaAhorro['ahorro_absoluto'], 2, ',', '.'), 'name' => $comparativaAhorro['segunda_super']]) }}
                        </div>
                    </section>
                @endif

                <section class="grid gap-5">
                    @foreach ($ranking as $fila)
                        <article class="overflow-hidden rounded-[15px] bg-white shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1">
                            <div class="grid gap-4 p-5 md:grid-cols-[1fr_repeat(5,auto)] md:items-center">
                                <div>
                                    <h2 class="text-xl font-semibold text-ink-900">{{ $fila['nombre_super'] }}</h2>
                                    <p class="mt-1 text-sm text-ink-500">{{ __('listas.recommendation.items', ['count' => $fila['items_cesta']]) }}</p>
                                    @if ($fila['es_combinada'])
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.08em] text-brand-600">
                                            {{ __('listas.recommendation.multi_store', ['count' => count($fila['supermercados'])]) }}
                                        </p>
                                    @endif
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">{{ __('listas.recommendation.total') }}</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['total_cesta'], 2, ',', '.') }} €</p>
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">{{ __('listas.recommendation.distance') }}</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['distancia_km'], 3, ',', '.') }} km</p>
                                </div>
                                <div class="text-sm">
                                    <p class="text-ink-500">{{ __('listas.recommendation.distance_cost') }}</p>
                                    <p class="font-semibold text-ink-900">{{ number_format((float) $fila['coste_distancia'], 2, ',', '.') }} €</p>
                                </div>
                                <div class="rounded-[10px] bg-brand-50 px-4 py-3 text-right">
                                    <p class="text-xs font-semibold uppercase text-brand-600">{{ __('listas.recommendation.score') }}</p>
                                    <p class="text-2xl font-bold text-brand-600">{{ number_format((float) $fila['score'], 2, ',', '.') }} €</p>
                                </div>
                                <div class="text-right">
                                    @if ($seleccionActualToken !== null && $seleccionActualToken === $fila['token'])
                                        <div class="rounded-[10px] bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">
                                            {{ __('listas.recommendation.selected') }}
                                        </div>
                                    @else
                                        <form action="{{ route('listas.recomendacion.elegir', $lista) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="combinacion" value="{{ $fila['token'] }}">
                                            <button class="ss-btn-green inline-flex w-full justify-center px-4 py-2.5 text-sm md:w-auto" type="submit">
                                                {{ __('listas.recommendation.choose') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <details class="border-t border-[var(--color-borde-suave)] bg-[var(--color-superficie-suave)] p-5" @if ($loop->first) open @endif>
                                <summary class="cursor-pointer list-none text-sm font-semibold text-brand-600">{{ __('listas.recommendation.breakdown') }}</summary>
                                <div class="mt-4 overflow-hidden rounded-[10px] border border-[var(--color-borde-suave)] bg-white">
                                    <table class="min-w-full divide-y divide-[var(--color-borde-suave)] text-sm text-ink-700">
                                        <thead class="bg-brand-50 text-left text-xs font-semibold uppercase text-ink-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('common.product') }}</th>
                                                <th class="px-4 py-3">{{ __('common.supermarket') }}</th>
                                                <th class="px-4 py-3">{{ __('common.quantity') }}</th>
                                                <th class="px-4 py-3">{{ __('listas.recommendation.unit_price') }}</th>
                                                <th class="px-4 py-3">{{ __('listas.recommendation.subtotal') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[var(--color-borde-suave)]">
                                            @foreach ($fila['detalle_cesta'] as $detalle)
                                                <tr>
                                                    <td class="px-4 py-3">{{ $detalle['nombre_producto'] }}</td>
                                                    <td class="px-4 py-3">{{ $detalle['nombre_super'] }}</td>
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

