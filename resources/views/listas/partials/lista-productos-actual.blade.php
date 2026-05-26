@forelse ($lista->productos as $producto)
    <article class="grid gap-4 border-b border-[var(--color-borde-suave)] p-4 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:gap-5 sm:p-5">
        <div class="flex min-w-0 items-center gap-3">
            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-[14px] border border-[var(--color-borde-suave)] bg-white">
                <img
                    src="{{ $producto->imagen_canonica ?? asset('img/productos/placeholder.svg') }}"
                    alt="{{ $producto->nombre_canonico }}"
                    class="h-full w-full object-cover"
                >
            </div>
            <div class="min-w-0">
                <h2 class="break-words text-base font-semibold leading-6 text-ink-900 sm:text-lg">{{ $producto->nombre_canonico }}</h2>
                <p class="mt-1 text-sm leading-5 text-ink-500">{{ $producto->marca_canonica ?? __('common.product_brand_fallback') }} · {{ $producto->formato_canonico ?? __('common.product_format_fallback') }}</p>
            </div>
        </div>

        @if ($puedeEditar)
            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <form action="{{ route('listas.productos.actualizar', [$lista, $producto]) }}" method="POST" data-lista-producto-update class="shrink-0">
                    @csrf
                    @method('PATCH')
                    <input
                        class="w-20 rounded-full border border-[var(--color-borde-suave)] bg-white px-3 py-2 text-center text-sm font-semibold text-ink-700 focus:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-200"
                        type="number"
                        name="cantidad"
                        min="1"
                        step="1"
                        value="{{ (int) $producto->pivot->cantidad }}"
                        data-lista-cantidad-input
                        required
                    >
                </form>

                <form action="{{ route('listas.productos.quitar', [$lista, $producto]) }}" method="POST" class="shrink-0">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-full p-3 text-rose-600 transition hover:bg-rose-50 hover:scale-105" type="submit" aria-label="{{ __('listas.products.remove_label', ['name' => $producto->nombre_producto]) }}">
                        <x-ui.icon name="trash" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        @else
            <div class="justify-self-start rounded-full border border-[var(--color-borde-suave)] px-5 py-2 font-semibold sm:justify-self-end">
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
