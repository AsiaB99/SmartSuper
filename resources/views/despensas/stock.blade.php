@extends('layouts.app')

@section('title', __('despensas.stock.meta_title'))

@section('content')
    <section class="ss-section">
        <div class="ss-container">
            <section class="mb-8 overflow-hidden rounded-[24px] bg-white shadow-[0_16px_40px_rgba(15,23,42,0.08)]">
                <div class="ss-header-gradient px-5 py-6 sm:px-8 sm:py-7">
                    <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-start lg:justify-between lg:gap-5">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('despensas.stock.kicker') }}</p>
                            <h1 class="mt-2 text-2xl font-semibold leading-tight text-ink-900 sm:text-4xl">{{ $despensa->nombre_despensa }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-600 sm:leading-7">{{ __('despensas.stock.subtitle') }}</p>
                        </div>
                        <a class="ss-btn-outline w-full self-start sm:w-auto" href="{{ route('despensas.index') }}">{{ __('common.back') }}</a>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <article class="rounded-[18px] border border-white/70 bg-white/85 px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">{{ __('despensas.stock.summary.products') }}</p>
                            <p class="mt-3 text-2xl font-semibold text-ink-900 sm:text-3xl" data-stock-total-productos>{{ $totalProductos }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ __('despensas.stock.summary.products_hint') }}</p>
                        </article>
                        <article class="rounded-[18px] border border-white/70 bg-white/85 px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">{{ __('despensas.stock.summary.units') }}</p>
                            <p class="mt-3 text-2xl font-semibold text-ink-900 sm:text-3xl" data-stock-unidades-totales>{{ $unidadesTotales }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ __('despensas.stock.summary.units_hint') }}</p>
                        </article>
                        <article class="rounded-[18px] border px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] {{ $productosBajos > 0 ? 'border-rose-200 bg-rose-50/90' : 'border-white/70 bg-white/85' }}">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-500' }}">{{ __('despensas.stock.summary.low') }}</p>
                            <p class="mt-3 text-2xl font-semibold {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-900' }} sm:text-3xl" data-stock-productos-bajos>{{ $productosBajos }}</p>
                            <p class="mt-1 text-sm {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-600' }}">{{ __('despensas.stock.summary.low_hint') }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-6">
                    <section class="ss-panel">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                            <h2 class="text-lg font-semibold text-ink-900 sm:text-xl">{{ __('despensas.stock.inventory_title') }}</h2>
                                <p class="mt-2 text-sm text-ink-600">{{ __('despensas.stock.inventory_text') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($busqueda !== '')
                                    <div id="stock-filter-chip" class="rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700">
                                        {{ __('despensas.stock.filtered_results', ['query' => $busqueda]) }}
                                    </div>
                                @else
                                    <div id="stock-filter-chip" class="hidden rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700"></div>
                                @endif
                            </div>
                        </div>

                        <form
                            id="buscador-stock-despensa"
                            class="mt-5 flex flex-wrap items-end gap-3 rounded-[18px] bg-[var(--color-fondo-claro)] p-4"
                            action="{{ route('despensas.stock', $despensa) }}"
                            method="GET"
                            data-stock-url="{{ route('despensas.stock', $despensa) }}"
                            data-sugerencias-url="{{ route('despensas.stock.sugerencias', $despensa) }}"
                            data-catalogo-sugerencias-url="{{ route('despensas.stock.catalogo-sugerencias', $despensa) }}"
                            data-low-threshold="{{ $lowStockThreshold }}"
                            data-filter-label="{{ __('despensas.stock.filtered_results_prefix') }}"
                            data-delete-template="{{ __('despensas.stock.delete_modal.text', ['name' => '__NAME__']) }}"
                            data-recommended-alert-template="{{ __('despensas.stock.recommended_alert', ['min' => '__MIN__']) }}"
                            data-add-to-list-url-template="{{ route('listas.productos.agregar', ['lista' => '__LISTA__']) }}"
                        >
                            <label class="grid w-full min-w-0 flex-1 gap-2 sm:min-w-[260px]">
                                <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.search_label') }}</span>
                                <div class="relative">
                                    <input
                                        id="busqueda-stock-despensa"
                                        class="ss-input min-h-[56px] w-full rounded-[16px]"
                                        type="search"
                                        name="q"
                                        value="{{ $busqueda }}"
                                        placeholder="{{ __('despensas.stock.search_placeholder') }}"
                                        autocomplete="off"
                                        aria-autocomplete="list"
                                        aria-expanded="false"
                                        aria-controls="sugerencias-stock-despensa"
                                    >
                                    <div
                                        id="sugerencias-stock-despensa"
                                        class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-30 hidden overflow-hidden rounded-[18px] border border-brand-100 bg-white shadow-[0_18px_36px_rgba(0,0,0,0.14)]"
                                    ></div>
                                </div>
                            </label>
                            <button class="ss-btn-outline inline-flex min-h-[56px] w-full items-center justify-center rounded-[16px] px-5 sm:w-auto" type="submit" aria-label="{{ __('despensas.stock.search_button_aria') }}">
                                <x-ui.icon name="magnifying-glass" class="h-5 w-5" />
                            </button>
                            @if ($busqueda !== '')
                                <a class="ss-btn-outline min-h-[56px] w-full rounded-[16px] px-5 sm:w-auto" href="{{ route('despensas.stock', $despensa) }}">{{ __('despensas.stock.clear') }}</a>
                            @endif
                        </form>
                    </section>

                    <div data-stock-wrapper>
                        @include('despensas.partials.stock-productos', ['despensa' => $despensa, 'puedeEditar' => $puedeEditar, 'lowStockThreshold' => $lowStockThreshold, 'tieneListasEditables' => $listasEditables->isNotEmpty()])
                    </div>
                </div>

                @if ($puedeEditar)
                    <aside class="min-w-0">
                        <details class="rounded-[20px] bg-white shadow-[0_10px_28px_rgba(15,23,42,0.06)]" @if ($errors->has('id_producto') || $errors->has('stock')) open @endif>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 px-6 py-5 marker:hidden">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('despensas.stock.manual.kicker') }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-ink-900">{{ __('despensas.stock.increment_title') }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('despensas.stock.manual.text') }}</p>
                                </div>
                            </summary>

                            <div class="border-t border-[var(--color-borde-suave)] px-6 py-5">
                                <form class="grid gap-5" action="{{ route('despensas.stock.agregar', $despensa) }}" method="POST" data-stock-manual-form>
                                    @csrf
                                    <input type="hidden" name="low_stock_threshold" value="{{ $lowStockThreshold }}">
                                    <label class="grid gap-2">
                                        <span class="text-sm font-semibold text-ink-700">{{ __('common.product') }}</span>
                                        <div class="relative">
                                            <input
                                                id="busqueda-producto-manual"
                                                class="ss-input min-h-[56px] w-full rounded-[16px] pr-12"
                                                type="search"
                                                placeholder="{{ __('despensas.stock.filter_product_placeholder') }}"
                                                autocomplete="off"
                                                value=""
                                            >
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex w-12 items-center justify-center text-ink-400">
                                                <x-ui.icon name="magnifying-glass" class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <select class="ss-input min-h-[56px] w-full rounded-[16px]" id="id_producto_manual" name="id_producto" required data-placeholder="{{ __('despensas.stock.select_product') }}">
                                            <option value="">{{ __('despensas.stock.select_product') }}</option>
                                            @if ($productoManualSeleccionado)
                                                <option value="{{ $productoManualSeleccionado->id }}" selected>
                                                    {{ $productoManualSeleccionado->nombre_producto }}{{ collect([$productoManualSeleccionado->marca, $productoManualSeleccionado->formato])->filter()->isNotEmpty() ? ' · ' . collect([$productoManualSeleccionado->marca, $productoManualSeleccionado->formato])->filter()->join(' · ') : '' }}
                                                </option>
                                            @endif
                                        </select>
                                        <p class="text-xs leading-5 text-ink-500">Escribe al menos 2 letras para cargar coincidencias en el selector.</p>
                                        @error('id_producto')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.add_quantity') }}</span>
                                        <input class="ss-input w-full text-right" type="number" name="stock" min="1" step="1" value="{{ old('stock', 1) }}" required>
                                        @error('stock')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                                    </label>
                                    <div class="hidden rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" data-stock-manual-feedback></div>
                                    <button class="ss-btn-green w-full justify-center" type="submit">{{ __('common.add') }}</button>
                                </form>
                            </div>
                        </details>
                    </aside>
                @endif
            </div>
        </div>
    </section>

    @if ($puedeEditar)
        <dialog id="stock-delete-modal" class="w-[min(92vw,420px)] rounded-[18px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_45px_rgba(15,23,42,0.24)] backdrop:bg-black/35">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-ink-900">{{ __('despensas.stock.delete_modal.title') }}</h3>
                <p id="stock-delete-modal-text" class="mt-3 text-sm leading-6 text-ink-600"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800" data-modal-cancel>{{ __('common.cancel') }}</button>
                    <form id="stock-delete-modal-form" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-[10px] bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-500" type="submit">{{ __('common.delete') }}</button>
                    </form>
                </div>
            </div>
        </dialog>

        <dialog id="stock-edit-modal" class="w-[min(92vw,460px)] rounded-[18px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_45px_rgba(15,23,42,0.24)] backdrop:bg-black/35">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-ink-900">{{ __('despensas.stock.edit_modal.title') }}</h3>
                <p id="stock-edit-modal-product" class="mt-2 text-sm text-ink-600"></p>
                <form id="stock-edit-modal-form" method="POST" class="mt-4 grid gap-4">
                    @csrf
                    @method('PATCH')
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.edit_modal.stock_label') }}</span>
                        <input id="stock-edit-modal-input" class="ss-input text-right" type="number" name="stock" min="0" step="1" required>
                    </label>
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.edit_modal.min_label') }}</span>
                        <input id="stock-edit-modal-min-input" class="ss-input text-right" type="number" min="1" max="99" step="1" required>
                    </label>
                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800" data-modal-cancel>{{ __('common.cancel') }}</button>
                        <button class="ss-btn-green" type="submit">{{ __('common.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </dialog>

        <dialog id="stock-add-to-list-modal" class="w-[min(92vw,460px)] rounded-[18px] border border-[var(--color-borde-suave)] p-0 shadow-[0_20px_45px_rgba(15,23,42,0.24)] backdrop:bg-black/35">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-ink-900">{{ __('despensas.stock.add_to_list_modal.title') }}</h3>
                <p id="stock-add-to-list-modal-product" class="mt-2 text-sm text-ink-600"></p>
                <form id="stock-add-to-list-modal-form" method="POST" class="mt-4 grid gap-4">
                    @csrf
                    <input id="stock-add-to-list-modal-product-id" type="hidden" name="id_producto" required>
                    <input type="hidden" name="redirect_despensa_id" value="{{ $despensa->id }}">

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.add_to_list_modal.list_label') }}</span>
                        <select id="stock-add-to-list-modal-lista" class="ss-input" required>
                            <option value="">{{ __('despensas.stock.add_to_list_modal.select_list') }}</option>
                            @foreach ($listasEditables as $listaEditable)
                                <option value="{{ $listaEditable->id }}">{{ $listaEditable->nombre_lista }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.add_to_list_modal.quantity_label') }}</span>
                        <input id="stock-add-to-list-modal-quantity" class="ss-input text-right" type="number" name="cantidad" min="1" step="1" value="1" required>
                    </label>

                    @if ($listasEditables->isEmpty())
                        <p class="rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            {{ __('despensas.stock.add_to_list_modal.empty') }}
                        </p>
                    @endif

                    <div class="flex justify-end gap-3">
                        <button type="button" class="rounded-[10px] border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-800 transition hover:border-brand-200 hover:text-brand-800" data-modal-cancel>{{ __('common.cancel') }}</button>
                        <button class="ss-btn-green" type="submit" @disabled($listasEditables->isEmpty())>{{ __('despensas.stock.add_to_list_modal.submit') }}</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif
@endsection

@push('scripts')
    @vite('resources/js/despensas-stock-page.js')
@endpush
