@extends('layouts.app')

@section('title', __('despensas.stock.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mb-8 overflow-hidden rounded-[24px] bg-white shadow-[0_16px_40px_rgba(15,23,42,0.08)]">
                <div class="bg-[radial-gradient(circle_at_top_left,_rgba(43,122,120,0.18),_transparent_48%),linear-gradient(135deg,#f7fbfa_0%,#ffffff_62%)] px-6 py-7 sm:px-8">
                    <div class="flex flex-wrap items-start justify-between gap-5">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('despensas.stock.kicker') }}</p>
                            <h1 class="mt-2 text-3xl font-semibold leading-tight text-ink-900 sm:text-4xl">{{ $despensa->nombre_despensa }}</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600">{{ __('despensas.stock.subtitle') }}</p>
                        </div>
                        <a class="ss-btn-outline self-start" href="{{ route('despensas.index') }}">{{ __('common.back') }}</a>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <article class="rounded-[18px] border border-white/70 bg-white/85 px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">{{ __('despensas.stock.summary.products') }}</p>
                            <p class="mt-3 text-3xl font-semibold text-ink-900">{{ $totalProductos }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ __('despensas.stock.summary.products_hint') }}</p>
                        </article>
                        <article class="rounded-[18px] border border-white/70 bg-white/85 px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-ink-500">{{ __('despensas.stock.summary.units') }}</p>
                            <p class="mt-3 text-3xl font-semibold text-ink-900">{{ $unidadesTotales }}</p>
                            <p class="mt-1 text-sm text-ink-600">{{ __('despensas.stock.summary.units_hint') }}</p>
                        </article>
                        <article class="rounded-[18px] border px-5 py-4 shadow-[0_10px_24px_rgba(15,23,42,0.06)] {{ $productosBajos > 0 ? 'border-rose-200 bg-rose-50/90' : 'border-white/70 bg-white/85' }}">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-500' }}">{{ __('despensas.stock.summary.low') }}</p>
                            <p class="mt-3 text-3xl font-semibold {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-900' }}">{{ $productosBajos }}</p>
                            <p class="mt-1 text-sm {{ $productosBajos > 0 ? 'text-rose-700' : 'text-ink-600' }}">{{ __('despensas.stock.summary.low_hint') }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    <section class="rounded-[20px] bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.06)] sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 class="text-xl font-semibold text-ink-900">{{ __('despensas.stock.inventory_title') }}</h2>
                                <p class="mt-2 text-sm text-ink-600">{{ __('despensas.stock.inventory_text') }}</p>
                            </div>
                            @if ($busqueda !== '')
                                <div class="rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700">
                                    {{ __('despensas.stock.filtered_results', ['query' => $busqueda]) }}
                                </div>
                            @endif
                        </div>

                        <form class="mt-5 flex flex-wrap items-end gap-3 rounded-[18px] bg-[var(--color-fondo-claro)] p-4" action="{{ route('despensas.stock', $despensa) }}" method="GET">
                            <label class="grid min-w-[260px] flex-1 gap-2">
                                <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.search_label') }}</span>
                                <input class="ss-input min-h-[56px] rounded-[16px]" type="search" name="q" value="{{ $busqueda }}" placeholder="{{ __('despensas.stock.search_placeholder') }}">
                            </label>
                            <button class="ss-btn-outline min-h-[56px] rounded-[16px] px-5" type="submit">{{ __('common.search') }}</button>
                            @if ($busqueda !== '')
                                <a class="ss-btn-outline min-h-[56px] rounded-[16px] px-5" href="{{ route('despensas.stock', $despensa) }}">{{ __('despensas.stock.clear') }}</a>
                            @endif
                        </form>
                    </section>

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
                                $estado = $stock <= 1 ? __('despensas.stock.low') : ($stock <= 3 ? __('despensas.stock.medium') : __('despensas.stock.high'));
                                $barClass = $stock <= 1 ? 'bg-[var(--color-alerta-roja)]' : ($stock <= 3 ? 'bg-amber-400' : 'bg-brand-500');
                                $badgeClass = $stock <= 1
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : ($stock <= 3 ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-brand-200 bg-brand-50 text-brand-700');
                            @endphp
                            <article class="border-b border-[var(--color-borde-suave)] px-5 py-5 last:border-b-0 sm:px-6 {{ $stock <= 1 ? 'bg-rose-50/70' : '' }}">
                                <div class="grid gap-4 md:grid-cols-[minmax(0,1.4fr)_110px_140px_220px] md:items-center">
                                    <div class="min-w-0">
                                        <div class="flex items-start gap-3">
                                            <div class="mt-1 flex h-11 w-11 shrink-0 items-center justify-center rounded-[14px] border border-[var(--color-borde-suave)] bg-white text-brand-500">
                                                <x-ui.icon name="archive-box" class="h-5 w-5" />
                                            </div>
                                            <div class="min-w-0">
                                                <h2 class="truncate text-lg font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                                                <p class="mt-1 text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('despensas.stock.basic') }}</p>
                                                @if ($stock <= 1)
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
                                            <span class="inline-flex rounded-full border px-3 py-1 text-sm font-semibold {{ $badgeClass }}">{{ $estado }}</span>
                                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-[var(--color-barra-suave)]">
                                                <div class="{{ $barClass }} h-full rounded-full" style="width: {{ $nivel }}%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($puedeEditar)
                                        <div class="flex flex-wrap items-end justify-end gap-3">
                                            <form action="{{ route('despensas.stock.actualizar', [$despensa, $producto]) }}" method="POST" class="flex items-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <label class="grid gap-2">
                                                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500 md:hidden">{{ __('despensas.stock.table.adjust') }}</span>
                                                    <input class="ss-input w-24 min-w-0 text-center" type="number" name="stock" min="0" step="1" value="{{ $stock }}" required>
                                                </label>
                                                <button class="ss-btn-outline px-4" type="submit">{{ __('despensas.stock.adjust') }}</button>
                                            </form>
                                            <form action="{{ route('despensas.stock.quitar', [$despensa, $producto]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-[10px] bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-500" type="submit">{{ __('despensas.stock.remove') }}</button>
                                            </form>
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
                </div>

                @if ($puedeEditar)
                    <aside>
                        <details class="overflow-hidden rounded-[20px] bg-white shadow-[0_10px_28px_rgba(15,23,42,0.06)]" @if ($errors->has('id_producto') || $errors->has('stock')) open @endif>
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 px-6 py-5 marker:hidden">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('despensas.stock.manual.kicker') }}</p>
                                    <h2 class="mt-2 text-xl font-semibold text-ink-900">{{ __('despensas.stock.increment_title') }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-ink-600">{{ __('despensas.stock.manual.text') }}</p>
                                </div>
                                <span class="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">{{ __('despensas.stock.manual.secondary') }}</span>
                            </summary>

                            <div class="border-t border-[var(--color-borde-suave)] px-6 py-5">
                                <form class="grid gap-5" action="{{ route('despensas.stock.agregar', $despensa) }}" method="POST">
                                    @csrf
                                    <label class="grid gap-2">
                                        <span class="text-sm font-semibold text-ink-700">{{ __('common.product') }}</span>
                                        <select class="ss-input" name="id_producto" required>
                                            <option value="">{{ __('despensas.stock.select_product') }}</option>
                                            @foreach ($productos as $producto)
                                                <option value="{{ $producto->id }}" @selected((int) old('id_producto') === $producto->id)>
                                                    {{ $producto->nombre_producto }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('id_producto')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.add_quantity') }}</span>
                                        <input class="ss-input" type="number" name="stock" min="1" step="1" value="{{ old('stock', 1) }}" required>
                                        @error('stock')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                                    </label>
                                    <button class="ss-btn-green w-full justify-center" type="submit">{{ __('common.add') }}</button>
                                </form>
                            </div>
                        </details>
                    </aside>
                @endif
            </div>
        </div>
    </section>
@endsection
