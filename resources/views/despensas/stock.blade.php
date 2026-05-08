@extends('layouts.app')

@section('title', __('despensas.stock.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-5">
                <div>
                    <h1 class="ss-title text-left">{{ __('despensas.stock.title') }}</h1>
                    <p class="mt-2 text-sm text-ink-600">{{ __('despensas.stock.pantry_label', ['name' => $despensa->nombre_despensa]) }}</p>
                </div>
                <a class="ss-btn-outline" href="{{ route('despensas.index') }}">{{ __('common.back') }}</a>
            </div>

            <form class="mb-6 flex flex-wrap items-end gap-3 rounded-[15px] bg-white p-5 shadow-[0_5px_15px_rgba(0,0,0,0.05)]" action="{{ route('despensas.stock', $despensa) }}" method="GET">
                <label class="grid min-w-[260px] flex-1 gap-2">
                    <span class="text-sm font-semibold text-ink-700">{{ __('despensas.stock.search_label') }}</span>
                    <input class="ss-input" type="search" name="q" value="{{ $busqueda }}" placeholder="{{ __('despensas.stock.search_placeholder') }}">
                </label>
                <button class="ss-btn-outline" type="submit">{{ __('common.search') }}</button>
                @if ($busqueda !== '')
                    <a class="ss-btn-outline" href="{{ route('despensas.stock', $despensa) }}">{{ __('despensas.stock.clear') }}</a>
                @endif
            </form>

            @if ($puedeEditar)
                <section class="mb-8 rounded-[15px] bg-white p-6 shadow-[0_5px_15px_rgba(0,0,0,0.05)]">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('despensas.stock.increment_title') }}</h2>
                    <form class="mt-5 grid gap-5 md:grid-cols-[1fr_180px_auto]" action="{{ route('despensas.stock.agregar', $despensa) }}" method="POST">
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
                        <button class="ss-btn-green self-end" type="submit">{{ __('common.add') }}</button>
                    </form>
                </section>
            @endif

            <section class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($despensa->productos as $producto)
                    @php
                        $stock = (int) $producto->pivot->stock;
                        $nivel = min(100, max(8, $stock * 20));
                        $nivelClass = "w-[{$nivel}%]";
                        $estado = $stock <= 1 ? __('despensas.stock.low') : ($stock <= 3 ? __('despensas.stock.medium') : __('despensas.stock.high'));
                        $barClass = $stock <= 1 ? 'bg-[var(--color-alerta-roja)]' : ($stock <= 3 ? 'bg-[#f1c40f]' : 'bg-brand-500');
                        $textClass = $stock <= 1 ? 'text-[var(--color-alerta-roja)]' : ($stock <= 3 ? 'text-[#b7950b]' : 'text-brand-500');
                    @endphp
                    <article class="flex flex-col rounded-[15px] bg-white p-6 shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1 {{ $stock <= 1 ? 'border-2 border-[#ffecec] bg-[#fffafa]' : '' }}">
                        <div class="mb-4 flex items-center gap-4">
                            <div class="flex h-[50px] w-[50px] items-center justify-center rounded-full border-2 border-[var(--color-borde-suave)] bg-brand-50 text-brand-500">
                                <x-ui.icon name="archive-box" class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                                <p class="text-xs text-ink-400">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('despensas.stock.basic') }}</p>
                            </div>
                        </div>

                        <div class="text-4xl font-bold text-ink-900">{{ $stock }} <span class="text-base font-normal text-ink-400">{{ __('common.units_short') }}</span></div>
                        <div class="my-3 h-[10px] overflow-hidden rounded-full bg-[var(--color-barra-suave)]">
                            <div class="{{ $barClass }} {{ $nivelClass }} h-full rounded-full"></div>
                        </div>
                        <p class="{{ $textClass }} text-sm font-bold">{{ $estado }}</p>

                        @if ($puedeEditar)
                            <div class="mt-5 flex flex-wrap gap-3">
                                <form action="{{ route('despensas.stock.actualizar', [$despensa, $producto]) }}" method="POST" class="flex flex-1 gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input class="ss-input w-24" type="number" name="stock" min="0" step="1" value="{{ $stock }}" required>
                                    <button class="ss-btn-outline flex-1" type="submit">{{ __('despensas.stock.adjust') }}</button>
                                </form>
                                <form action="{{ route('despensas.stock.quitar', [$despensa, $producto]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-[8px] bg-rose-600 px-4 py-3 font-semibold text-white transition hover:bg-rose-500" type="submit">{{ __('despensas.stock.remove') }}</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 md:col-span-2 lg:col-span-3">
                        <h2 class="text-xl font-semibold text-ink-900">{{ __('despensas.stock.empty.title') }}</h2>
                        <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('despensas.stock.empty.text') }}</p>
                    </div>
                @endforelse
            </section>
        </div>
    </section>
@endsection
