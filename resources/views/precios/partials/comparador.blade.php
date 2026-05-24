@php
    $preciosOrdenados = $precios->sortBy('precio')->values();
    $precioMin = $preciosOrdenados->min('precio');
    $precioMax = $preciosOrdenados->max('precio');
    $diferenciaMaxima = ($precioMin !== null && $precioMax !== null) ? max(0, (float) $precioMax - (float) $precioMin) : null;
@endphp

<div class="flex flex-wrap items-start gap-4">
    @if ($productoSeleccionado)
        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-[18px] border border-[var(--color-borde-suave)] bg-white">
            <img
                src="{{ $productoSeleccionado->imagen_canonica ?? asset('img/productos/placeholder.svg') }}"
                alt="{{ $productoSeleccionado->nombre_canonico }}"
                class="h-full w-full object-cover"
            >
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Comparativa</p>
        <h2 class="mt-2 text-3xl font-semibold leading-tight text-ink-900">
            {{ $productoSeleccionado?->nombre_canonico ?? __('precios.index.select_product') }}
        </h2>

        @if ($productoSeleccionado)
            <p class="mt-2 text-sm text-ink-500">
                {{ $productoSeleccionado->marca_canonica ?? __('common.product_brand_fallback') }} · {{ $productoSeleccionado->formato_canonico ?? __('common.product_format_fallback') }}
            </p>
        @else
            <p class="mt-2 text-sm text-ink-500">Busca primero un producto para ver aqu&iacute; la comparativa de cadenas.</p>
        @endif
    </div>
</div>

@if ($productoSeleccionado && $preciosOrdenados->isNotEmpty())
    <div class="mt-6 rounded-[18px] border border-brand-100 bg-[linear-gradient(145deg,#f7fcf9_0%,#edf8f2_100%)] p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-brand-700">Resumen rápido</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-[14px] bg-white/80 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">Mejor precio</p>
                <p class="mt-2 text-2xl font-bold text-brand-700">{{ number_format((float) $precioMin, 2, ',', '.') }} €</p>
                <p class="mt-1 text-sm text-ink-700">{{ $preciosOrdenados->first()->nombre_super }}</p>
            </div>

            @if ($diferenciaMaxima !== null && $diferenciaMaxima > 0)
                <div class="rounded-[14px] bg-white/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">Ahorro máximo</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format((float) $diferenciaMaxima, 2, ',', '.') }} €</p>
                    <p class="mt-1 text-sm text-ink-700">Entre mejor y peor precio visible</p>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="mt-5 grid gap-4">
    @if (! $productoSeleccionado)
        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
            <h3 class="text-xl font-semibold text-ink-900">Esperando búsqueda</h3>
            <p class="mt-2 text-sm leading-7 text-ink-600">En cuanto elijas un producto, aquí verás su comparativa de precios por cadena.</p>
        </div>
    @else
        @forelse ($preciosOrdenados as $precio)
            @php
                $esMejor = (float) $precio->precio === (float) $mejorPrecio;
                $diferencia = $precioMin !== null ? max(0, (float) $precio->precio - (float) $precioMin) : 0;
                $anchoBarra = ($precioMax !== null && (float) $precioMax > 0)
                    ? max(8, min(100, ((float) $precio->precio / (float) $precioMax) * 100))
                    : 100;
            @endphp

            <article class="rounded-[18px] border border-[var(--color-borde-suave)] bg-[var(--color-fondo-claro)] p-5 shadow-[0_5px_15px_rgba(0,0,0,0.05)]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-ink-900">{{ $precio->nombre_super }}</h3>
                            @if ($esMejor)
                                <span class="rounded-full bg-[var(--color-exito-suave)] px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.08em] text-brand-700">{{ __('precios.index.best') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="text-right">
                        <strong class="text-3xl {{ $esMejor ? 'text-brand-700' : 'text-ink-900' }}">{{ number_format((float) $precio->precio, 2, ',', '.') }} €</strong>
                        @if ($diferencia > 0.001)
                            <p class="mt-1 text-sm font-semibold text-amber-700">+{{ number_format((float) $diferencia, 2, ',', '.') }} €</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-emerald-700">Referencia</p>
                        @endif
                    </div>
                </div>

                <div class="mt-4">
                    <div class="h-2.5 overflow-hidden rounded-full bg-[#e7efe9]">
                        <div
                            class="h-full rounded-full transition-all {{ $esMejor ? 'bg-emerald-500' : ((float) $precio->precio === (float) $precioMax ? 'bg-rose-400' : 'bg-brand-500') }}"
                            style="width: {{ number_format($anchoBarra, 2, '.', '') }}%"
                        ></div>
                    </div>
                </div>

                @if ($precio->precio_unidad !== null || $precio->unidad_ref)
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-ink-600">
                        @if ($precio->precio_unidad !== null)
                            <span class="rounded-full bg-white px-3 py-1.5 font-medium">
                                {{ number_format((float) $precio->precio_unidad, 2, ',', '.') }} € / {{ $precio->unidad_ref ?? __('common.unit_default') }}
                            </span>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                <h3 class="text-xl font-semibold text-ink-900">{{ __('precios.index.empty.title') }}</h3>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('precios.index.empty.text') }}</p>
            </div>
        @endforelse
    @endif
</div>
