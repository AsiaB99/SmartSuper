<section class="overflow-hidden rounded-[20px] bg-white shadow-[0_10px_28px_rgba(15,23,42,0.06)]">
    <div class="hidden grid-cols-[minmax(0,1.4fr)_110px_140px_220px] gap-4 border-b border-[var(--color-borde-suave)] bg-[var(--color-fondo-claro)] px-6 py-4 text-xs font-semibold uppercase tracking-[0.14em] text-ink-500 md:grid">
        <span>{{ __('common.product') }}</span>
        <span>{{ __('despensas.stock.table.stock') }}</span>
        <span>{{ __('despensas.stock.table.status') }}</span>
        <span class="text-right">{{ __('despensas.stock.table.actions') }}</span>
    </div>

    @forelse ($despensa->productos as $producto)
        @php
            $stock = (int) $producto->pivot->stock;
            $nivel = min(100, max(8, $stock * 20));
            $tieneStockNoAlto = $stock <= ($lowStockThreshold + 2);
            $estado = $stock <= $lowStockThreshold ? __('despensas.stock.low') : ($stock <= ($lowStockThreshold + 2) ? __('despensas.stock.medium') : __('despensas.stock.high'));
            $barClass = $stock <= $lowStockThreshold ? 'bg-[var(--color-alerta-roja)]' : ($stock <= ($lowStockThreshold + 2) ? 'bg-amber-400' : 'bg-brand-500');
            $widthClass = match ($nivel) {
                8 => 'w-[8%]',
                20 => 'w-[20%]',
                40 => 'w-[40%]',
                60 => 'w-[60%]',
                80 => 'w-[80%]',
                default => 'w-full',
            };
            $badgeClass = $stock <= $lowStockThreshold
                ? 'border-rose-200 bg-rose-50 text-rose-700'
                : ($stock <= ($lowStockThreshold + 2) ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-brand-200 bg-brand-50 text-brand-700');
        @endphp
        <article class="border-b border-[var(--color-borde-suave)] px-5 py-5 last:border-b-0 sm:px-6 {{ $stock <= $lowStockThreshold ? 'bg-rose-50/70' : '' }}" data-product-row data-product-id="{{ $producto->id }}" data-stock-value="{{ $stock }}">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_110px_140px_220px] md:items-center">
                <div class="min-w-0">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-[14px] border border-[var(--color-borde-suave)] bg-white text-brand-500">
                            <x-ui.icon name="archive-box" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                            <p class="mt-1 text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('despensas.stock.basic') }}</p>
                            @if ($stock <= $lowStockThreshold)
                                <p class="mt-2 text-sm font-medium text-rose-700">{{ __('despensas.stock.low_notice') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500 md:hidden">{{ __('despensas.stock.table.stock') }}</p>
                    <div class="mt-1 flex items-end gap-2 md:mt-0">
                        <span class="text-3xl font-semibold text-ink-900">{{ $stock }}</span>
                        <span class="pb-1 text-sm text-ink-500">{{ __('common.units_short') }}</span>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500 md:hidden">{{ __('despensas.stock.table.status') }}</p>
                    <div class="mt-2 md:mt-0">
                        <span class="inline-flex rounded-full border px-3 py-1 text-sm font-semibold {{ $badgeClass }}" data-status-badge>{{ $estado }}</span>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--color-barra-suave)]">
                            <div class="{{ $barClass }} {{ $widthClass }} h-full rounded-full"></div>
                        </div>
                        <p class="mt-2 hidden text-xs font-medium text-rose-700" data-recommended-alert></p>
                    </div>
                </div>

                @if ($puedeEditar)
                    <div class="flex flex-nowrap items-center justify-end gap-2">
                        @if ($tieneStockNoAlto)
                            <button
                                class="ss-btn-outline inline-flex shrink-0 items-center justify-center gap-1 px-3 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                data-stock-add-to-list-open
                                data-product-id="{{ $producto->id }}"
                                data-product-name="{{ $producto->nombre_producto }}"
                                @disabled(! ($tieneListasEditables ?? false))
                                aria-label="{{ __('despensas.stock.add_to_list_label', ['name' => $producto->nombre_producto]) }}"
                            >
                                <x-ui.icon name="plus" class="h-4 w-4" />
                                <x-ui.icon name="list-bullet" class="h-4 w-4" />
                                <span class="sr-only">{{ __('despensas.stock.add_to_list') }}</span>
                            </button>
                        @endif
                        <button
                            class="ss-btn-outline inline-flex items-center justify-center"
                            type="button"
                            data-stock-edit-open
                            data-action="{{ route('despensas.stock.actualizar', [$despensa, $producto]) }}"
                            data-product-id="{{ $producto->id }}"
                            data-product-name="{{ $producto->nombre_producto }}"
                            data-stock="{{ $stock }}"
                            aria-label="{{ __('despensas.stock.edit_stock_label', ['name' => $producto->nombre_producto]) }}"
                        >
                            <x-ui.icon name="pencil-square" class="h-5 w-5" />
                        </button>
                        <button
                            class="inline-flex items-center justify-center rounded-[10px] bg-rose-600 px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500"
                            type="button"
                            data-stock-delete-open
                            data-action="{{ route('despensas.stock.quitar', [$despensa, $producto]) }}"
                            data-product-name="{{ $producto->nombre_producto }}"
                            aria-label="{{ __('despensas.stock.remove_label', ['name' => $producto->nombre_producto]) }}"
                        >
                            <x-ui.icon name="trash" class="h-5 w-5" />
                        </button>
                    </div>
                @else
                    <div class="text-sm text-ink-500 md:text-right">{{ __('despensas.stock.read_only') }}</div>
                @endif
            </div>
        </article>
    @empty
        <div class="p-6 sm:p-8">
            <div class="rounded-[18px] border border-dashed border-brand-200 bg-white p-6">
                <h2 class="text-xl font-semibold text-ink-900">{{ __('despensas.stock.empty.title') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('despensas.stock.empty.text') }}</p>
            </div>
        </div>
    @endforelse
</section>
