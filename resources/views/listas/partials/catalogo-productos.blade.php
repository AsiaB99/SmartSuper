@if ($puedeEditar)
    @if (($busqueda ?? '') === '')
        <div class="rounded-[10px] border border-dashed border-brand-200 bg-brand-50 p-5">
            <h3 class="text-base font-semibold text-ink-900">Busca un producto para empezar</h3>
            <p class="mt-2 text-sm leading-6 text-ink-600">Escribe nombre, marca o formato y te mostraremos solo el cat&aacute;logo relevante para a&ntilde;adir a la lista.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($productos as $producto)
                <article class="flex h-full flex-col rounded-[16px] border border-[var(--color-borde-suave)] bg-[var(--color-fondo-claro)] p-4 shadow-[0_3px_10px_rgba(0,0,0,0.04)]">
                    <div class="mx-auto w-full max-w-[180px] overflow-hidden rounded-[18px] border border-[var(--color-borde-suave)] bg-white">
                        <div class="aspect-[4/3] w-full">
                            <img
                                src="{{ $producto->imagen_canonica ?? asset('img/productos/placeholder.svg') }}"
                                alt="{{ $producto->nombre_canonico }}"
                                class="h-full w-full object-cover"
                            >
                        </div>
                    </div>

                    <div class="mt-4 min-w-0 flex-1">
                        <h3 class="text-lg font-semibold leading-6 text-ink-900">{{ $producto->nombre_canonico }}</h3>
                        <p class="mt-2 text-sm text-ink-500">{{ $producto->marca_canonica ?? __('common.product_brand_fallback') }} · {{ $producto->formato_canonico ?? __('common.product_format_fallback') }}</p>
                    </div>

                    <form class="mt-5 flex flex-1 flex-col justify-end gap-2" action="{{ route('listas.productos.agregar', $lista) }}" method="POST">
                        @csrf
                        <input type="hidden" name="id_producto" value="{{ $producto->id }}">
                        <input type="hidden" name="cantidad" value="1">
                        <input type="hidden" name="q" value="{{ $busqueda }}">
                        <input type="hidden" name="page" value="{{ $productos->currentPage() }}">
                        @if ((int) old('id_producto') === $producto->id)
                            @error('cantidad')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                        @endif
                        <button class="ss-btn-green w-full justify-center px-4 py-2.5 text-sm" type="submit" data-catalogo-add>{{ __('listas.products.catalog_add') }}</button>
                    </form>
                </article>
            @empty
                <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-5 sm:col-span-2">
                    <h3 class="text-base font-semibold text-ink-900">{{ __('listas.products.catalog_empty.title') }}</h3>
                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('listas.products.catalog_empty.text') }}</p>
                </div>
            @endforelse
        </div>
    @endif

    @if ($productos->hasPages())
        <div class="mt-5" data-catalogo-paginacion>
            {{ $productos->links() }}
        </div>
    @endif
@endif
