@extends('layouts.app')

@section('title', __('listas.products.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <div class="ss-header-gradient mb-8 flex flex-wrap items-start justify-between gap-5 rounded-[24px] px-6 py-6 shadow-[0_14px_35px_rgba(0,0,0,0.06)] sm:px-8">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('listas.products.kicker') }}</p>
                        <h1 class="mt-2 truncate text-4xl font-semibold leading-tight text-ink-900">{{ $lista->nombre_lista }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600">{{ __('listas.products.subtitle') }}</p>
                    </div>
                </div>
                <a class="ss-btn-outline self-center" href="{{ route('listas.index') }}">{{ __('common.back') }}</a>
            </div>

            @if ($puedeEditar)
                <section class="mb-8 rounded-[20px] bg-white p-5 shadow-[0_10px_28px_rgba(0,0,0,0.06)] sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold text-ink-900">{{ __('listas.products.add_title') }}</h2>
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
                            <div class="flex items-center gap-2">
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
                                <button class="ss-btn-outline inline-flex min-h-[58px] items-center justify-center rounded-[16px] px-5" type="submit" aria-label="{{ __('listas.products.search_button_aria') }}">
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

                    <div class="mt-5" data-catalogo-wrapper>
                        @include('listas.partials.catalogo-productos', ['lista' => $lista, 'productos' => $productos, 'puedeEditar' => $puedeEditar])
                    </div>
                </section>
            @endif

            <div class="grid gap-10 lg:grid-cols-[1fr_330px]">
                <section class="rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]">
                    @forelse ($lista->productos as $producto)
                        <article class="flex flex-wrap items-center justify-between gap-4 border-b border-[var(--color-borde-suave)] p-4 last:border-b-0 sm:p-5">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold text-ink-900">{{ $producto->nombre_producto }}</h2>
                                <p class="text-sm text-ink-500">{{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('listas.products.product_fallback') }}</p>
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
                </section>

                <x-listas.resumen-aside class="p-10">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('common.summary') }}</h2>
                    <p class="mt-3 text-sm text-ink-600">{{ __('listas.products.summary.count') }}</p>
                    <p class="mt-3 text-3xl font-bold text-ink-900 text-center">{{ $lista->productos->count() }}</p>
                    @if ($puedeEditar && (auth()->user()?->latitud === null || auth()->user()?->longitud === null))
                        <div class="mt-8 rounded-[12px] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                            {{ __('listas.products.location_notice') }}
                            <form id="form-ubicacion-usuario" class="mt-3" action="{{ route('profile.location.update') }}" method="POST">
                                @csrf
                                <input type="hidden" name="latitud" id="ubicacion-latitud">
                                <input type="hidden" name="longitud" id="ubicacion-longitud">
                                <button type="button" id="btn-usar-ubicacion" class="ss-btn-outline w-full justify-center" data-loading-text="{{ __('js.location.loading') }}" data-error-text="{{ __('js.location.error') }}">
                                    {{ __('listas.products.use_location') }}
                                </button>
                            </form>
                        </div>
                    @endif
                    <a class="ss-btn-green mt-8 w-full text-center {{ ! $puedeEditar ? 'pointer-events-none opacity-60' : '' }}"
                       href="{{ $puedeEditar ? route('listas.recomendacion', $lista) : '#' }}">
                        {{ __('listas.products.recommendation') }}
                    </a>
                </x-listas.resumen-aside>
            </div>
        </div>
    </section>

@endsection
