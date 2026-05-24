@extends('layouts.app')

@section('title', __('listas.products.meta_title'))

@section('content')
    <section class="ss-section">
        <div class="ss-container">
            <div class="ss-header-gradient ss-page-header">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('listas.products.kicker') }}</p>
                        <h1 class="ss-page-header-title truncate">{{ $lista->nombre_lista }}</h1>
                        <p class="ss-page-header-copy">{{ __('listas.products.subtitle') }}</p>
                    </div>
                </div>
                <a class="ss-btn-outline w-full self-center sm:w-auto" href="{{ route('listas.index') }}">{{ __('common.back') }}</a>
            </div>

            @if ($puedeEditar)
                <section class="ss-panel mb-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-ink-900 sm:text-2xl">{{ __('listas.products.add_title') }}</h2>
                            <p class="mt-2 text-sm text-ink-600">{{ __('listas.products.add_text') }}</p>
                        </div>
                    </div>

                    <form
                        id="buscador-catalogo-lista"
                        class="mt-5 grid gap-4 rounded-[18px] bg-[var(--color-fondo-claro)] p-4 sm:p-5"
                        action="{{ route('listas.productos', $lista) }}"
                        method="GET"
                        data-catalogo-url="{{ route('listas.productos', $lista) }}"
                        data-sugerencias-url="{{ route('listas.productos.sugerencias', $lista) }}"
                    >
                        <label class="grid w-full gap-2">
                            <span class="text-sm font-semibold text-ink-700">{{ __('listas.products.search_label') }}</span>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <div class="relative flex-1">
                                    <input
                                        id="busqueda-productos-lista"
                                        class="ss-input min-h-[58px] w-full rounded-[16px] px-5 text-base"
                                        type="search"
                                        name="q"
                                        value="{{ $busqueda }}"
                                        placeholder="{{ __('listas.products.search_placeholder') }}"
                                        autocomplete="off"
                                        aria-autocomplete="list"
                                        aria-expanded="false"
                                        aria-controls="sugerencias-productos-lista"
                                    >
                                    <div
                                        id="sugerencias-productos-lista"
                                        class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-30 hidden overflow-hidden rounded-[18px] border border-brand-100 bg-white shadow-[0_18px_36px_rgba(0,0,0,0.14)]"
                                    ></div>
                                </div>
                                <button class="ss-btn-outline inline-flex min-h-[58px] w-full items-center justify-center rounded-[16px] px-5 sm:w-auto" type="submit" aria-label="{{ __('listas.products.search_button_aria') }}">
                                    <x-ui.icon name="magnifying-glass" class="h-5 w-5" />
                                </button>
                            </div>
                        </label>
                    </form>

                    @if ($errors->has('id_producto') || $errors->has('cantidad'))
                        <div class="mt-5 rounded-[10px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                            {{ __('listas.products.validation_error') }}
                        </div>
                    @endif

                    <div class="mt-5 hidden rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" data-catalogo-feedback></div>

                    <div class="mt-5" data-catalogo-wrapper>
                        @include('listas.partials.catalogo-productos', ['lista' => $lista, 'productos' => $productos, 'busqueda' => $busqueda, 'puedeEditar' => $puedeEditar])
                    </div>
                </section>
            @endif

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]" data-lista-productos-actual>
                    @include('listas.partials.lista-productos-actual', ['lista' => $lista, 'puedeEditar' => $puedeEditar])
                </section>

                <x-listas.resumen-aside class="p-6 sm:p-10" data-lista-resumen>
                    @include('listas.partials.resumen-productos', ['lista' => $lista, 'puedeEditar' => $puedeEditar])
                </x-listas.resumen-aside>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    @vite('resources/js/listas-productos-page.js')
@endpush
