@forelse ($lista->productos as $producto)
    <article class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--color-borde-suave)] p-4 last:border-b-0 sm:p-5">
        <div class="flex min-w-0 items-center gap-3">
            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-[14px] border border-[var(--color-borde-suave)] bg-white">
                <img
                    src="{{ $producto->imagen_canonica ?? asset('img/productos/placeholder.svg') }}"
                    alt="{{ $producto->nombre_canonico }}"
                    class="h-full w-full object-cover"
                >
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-ink-900">{{ $producto->nombre_canonico }}</h2>
                <p class="text-sm text-ink-500">{{ $producto->marca_canonica ?? __('common.product_brand_fallback') }} · {{ $producto->formato_canonico ?? __('common.product_format_fallback') }}</p>
            </div>
        </div>

        @if ($puedeEditar)
            <div class="flex flex-wrap items-center gap-3">
                <form action="{{ route('listas.productos.actualizar', [$lista, $producto]) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <input
                        class="w-20 rounded-full border border-[var(--color-borde-suave)] bg-white px-3 py-2 text-center text-sm font-semibold text-ink-700 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200"
                        type="number"
                        name="cantidad"
                        min="1"
                        step="1"
                        value="{{ (int) $producto->pivot->cantidad }}"
                        onchange="this.form.submit()"
                        required
                    >
                </form>

                <form action="{{ route('listas.productos.quitar', [$lista, $producto]) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button class="p-3 text-rose-600 transition hover:scale-110" type="submit" aria-label="{{ __('listas.products.remove_label', ['name' => $producto->nombre_producto]) }}">
                        <x-ui.icon name="trash" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-full border border-[var(--color-borde-suave)] px-5 py-2 font-semibold">
                {{ (int) $producto->pivot->cantidad }}
            </div>
        @endif
    </article>
@empty
    <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
        <h2 class="text-xl font-semibold text-ink-900">{{ __('listas.products.empty.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('listas.products.empty.text') }}</p>
    </div>
@endforelse
