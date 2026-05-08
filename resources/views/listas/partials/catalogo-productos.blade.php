@if ($puedeEditar)
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($productos as $producto)
            <article class="flex h-full flex-col rounded-[12px] border border-[var(--color-borde-suave)] bg-[var(--color-fondo-claro)] p-4 shadow-[0_3px_10px_rgba(0,0,0,0.04)]">
                <div class="flex items-start gap-3">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold leading-5 text-ink-900">{{ $producto->nombre_producto }}</h3>
                        <p class="mt-1 text-xs text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('listas.products.catalog_fallback') }}</p>
                    </div>
                </div>

                <form class="mt-4 flex flex-1 flex-col justify-end gap-2" action="{{ route('listas.productos.agregar', $lista) }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_producto" value="{{ $producto->id }}">
                    <input type="hidden" name="cantidad" value="1">
                    @if ((int) old('id_producto') === $producto->id)
                        @error('cantidad')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    @endif
                    <button class="ss-btn-green w-full justify-center px-4 py-2.5 text-sm" type="submit">{{ __('listas.products.catalog_add') }}</button>
                </form>
            </article>
        @empty
            <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-5 sm:col-span-2 lg:col-span-3">
                <h3 class="text-base font-semibold text-ink-900">{{ __('listas.products.catalog_empty.title') }}</h3>
                <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('listas.products.catalog_empty.text') }}</p>
            </div>
        @endforelse
    </div>

    @if ($productos->hasPages())
        <div class="mt-5" data-catalogo-paginacion>
            {{ $productos->links() }}
        </div>
    @endif
@endif
