@if ($busqueda === '')
    <div class="rounded-[10px] border border-dashed border-brand-200 bg-brand-50 p-5">
        <h3 class="text-base font-semibold text-ink-900">Busca un producto para empezar</h3>
        <p class="mt-2 text-sm leading-6 text-ink-600">Escribe nombre, marca o formato y te mostraremos el cat&aacute;logo relevante.</p>
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2">
        @forelse ($productos as $producto)
            <a
                href="{{ route('precios.index', array_filter(['busqueda' => $busqueda, 'producto' => $producto->id])) }}"
                data-precio-producto
                data-producto-id="{{ $producto->id }}"
                class="group flex h-full flex-col rounded-[16px] border p-4 text-left shadow-[0_3px_10px_rgba(0,0,0,0.04)] transition hover:-translate-y-0.5 {{ $productoId === $producto->id ? 'border-brand-300 bg-brand-50' : 'border-[var(--color-borde-suave)] bg-[var(--color-fondo-claro)] hover:border-brand-200' }}"
            >
                <div class="flex items-start gap-3">
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-[14px] border border-[var(--color-borde-suave)] bg-white sm:h-16 sm:w-16">
                        <img
                            src="{{ $producto->imagen_canonica ?? asset('img/productos/placeholder.svg') }}"
                            alt="{{ $producto->nombre_canonico }}"
                            class="h-full w-full object-cover"
                        >
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <h3 class="text-sm font-semibold leading-5 text-ink-900 sm:text-base">{{ $producto->nombre_canonico }}</h3>
                            @if ($productoId === $producto->id)
                                <span class="w-fit rounded-full bg-brand-600 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-white">
                                    Seleccionado
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-ink-500">
                            {{ $producto->marca_canonica ?? __('common.product_brand_fallback') }} · {{ $producto->formato_canonico ?? __('common.product_format_fallback') }}
                        </p>
                        @if ($producto->mejor_cadena_nombre !== null && $producto->mejor_cadena_precio !== null)
                            <p class="mt-3 text-xs font-medium text-emerald-700">
                                Más barato en {{ $producto->mejor_cadena_nombre }} · {{ number_format((float) $producto->mejor_cadena_precio, 2, ',', '.') }} €
                            </p>
                        @endif
                        <span class="mt-3 inline-flex items-center text-sm font-semibold text-brand-700 group-hover:text-brand-800 sm:mt-4">
                            Ver comparativa
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-5 sm:col-span-2">
                <h3 class="text-base font-semibold text-ink-900">{{ __('precios.index.empty.title') }}</h3>
                <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('precios.index.empty_search') }}</p>
            </div>
        @endforelse
    </div>
@endif

@if ($productos->hasPages())
    <div class="mt-5" data-precios-paginacion>
        {{ $productos->links() }}
    </div>
@endif
