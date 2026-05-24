@extends('layouts.app')

@section('title', __('precios.index.meta_title'))

@section('content')
    <section class="ss-section">
        <div class="ss-container">
            <section class="relative mb-10 overflow-hidden rounded-[24px] px-6 py-8 shadow-[0_12px_32px_rgba(0,0,0,0.06)] sm:px-8 sm:py-10">
                <img
                    src="{{ asset('img/encabezados/encabezado_super.PNG') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-white/75" aria-hidden="true"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-5">
                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('precios.index.title') }}</p>
                        <h1 class="mt-2 text-4xl font-semibold leading-tight text-ink-900">{{ __('precios.index.title') }}</h1>
                        <p class="mt-3 text-sm leading-7 text-ink-600">{{ __('precios.index.search_placeholder') }}</p>
                    </div>

                </div>

                <form
                    id="buscador-precios"
                    class="relative mt-6 grid gap-3 rounded-[20px] bg-white/90 p-4 shadow-[0_10px_25px_rgba(0,0,0,0.06)] sm:p-5"
                    action="{{ route('precios.index') }}"
                    method="GET"
                    data-precios-url="{{ route('precios.index') }}"
                >
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">{{ __('precios.index.products') }}</span>
                        <div class="flex items-center gap-2">
                            <input
                                id="busqueda-precios"
                                class="ss-input min-h-[58px] w-full rounded-[16px] px-5 text-base"
                                type="search"
                                name="busqueda"
                                value="{{ $busqueda }}"
                                placeholder="{{ __('precios.index.search_placeholder') }}"
                                autocomplete="off"
                            >
                            <button class="ss-btn-outline inline-flex min-h-[58px] items-center justify-center rounded-[16px] px-5" type="submit" aria-label="{{ __('common.search') }}">
                                <x-ui.icon name="magnifying-glass" class="h-5 w-5" />
                            </button>
                        </div>
                    </label>
                </form>
            </section>

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)] xl:items-start">
                <section class="rounded-[20px] bg-white p-5 shadow-[0_10px_28px_rgba(0,0,0,0.06)] sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-semibold text-ink-900">{{ __('precios.index.products') }}</h2>
                            <p class="mt-2 text-sm text-ink-600">{{ __('precios.index.search_placeholder') }}</p>
                        </div>
                    </div>

                    <div class="mt-5" data-precios-productos>
                        @include('precios.partials.productos', ['busqueda' => $busqueda, 'productoId' => $productoId, 'productos' => $productos])
                    </div>
                </section>

                <section class="rounded-[20px] bg-white p-5 shadow-[0_10px_28px_rgba(0,0,0,0.06)] sm:p-6" data-precios-comparador>
                    @include('precios.partials.comparador', ['productoSeleccionado' => $productoSeleccionado, 'precios' => $precios, 'mejorPrecio' => $mejorPrecio])
                </section>
            </div>
        </div>
    </section>
@endsection
