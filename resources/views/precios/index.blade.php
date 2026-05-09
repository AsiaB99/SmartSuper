@extends('layouts.app')

@section('title', __('precios.index.meta_title'))

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="relative mb-12 overflow-hidden rounded-[20px] p-10 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <img
                    src="{{ asset('img/encabezados/encabezado_super.PNG') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-white/60" aria-hidden="true"></div>
                <h1 class="relative text-4xl font-semibold text-ink-900">{{ __('precios.index.title') }}</h1>
                <form method="GET" action="{{ route('precios.index') }}" class="relative mx-auto mt-5 flex max-w-[600px]">
                    <input
                        id="busqueda-producto"
                        name="busqueda"
                        type="search"
                        value="{{ $busqueda }}"
                        class="w-full rounded-full border-2 border-[var(--color-borde-suave)] px-6 py-4 pr-32 text-base shadow-[0_5px_15px_rgba(0,0,0,0.03)] focus:border-brand-500 focus:ring-brand-500"
                        placeholder="{{ __('precios.index.search_placeholder') }}"
                    >
                    <button type="submit" class="absolute bottom-1 right-1 top-1 inline-flex items-center gap-2 rounded-full bg-brand-500 px-6 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <x-ui.icon name="magnifying-glass" class="h-4 w-4" />
                        <span>{{ __('common.search') }}</span>
                    </button>
                </form>
            </section>

            <div class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr]">
                <section class="ss-card">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('precios.index.products') }}</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse ($productos as $producto)
                            <a
                                href="{{ route('precios.index', array_filter(['busqueda' => $busqueda, 'producto' => $producto->id])) }}"
                                class="rounded-[10px] border px-4 py-3 text-left transition hover:-translate-y-0.5 {{ $productoId === $producto->id ? 'border-brand-400 bg-brand-50 text-brand-900' : 'border-[var(--color-borde-suave)] bg-[var(--color-superficie-suave)] text-ink-800' }}"
                            >
                                <span class="block font-semibold">{{ $producto->nombre_producto }}</span>
                                <span class="mt-1 block text-xs text-ink-500">
                                    {{ collect([$producto->marca, $producto->formato])->filter()->join(' · ') ?: __('precios.index.format_fallback') }}
                                </span>
                            </a>
                        @empty
                            <div class="rounded-[10px] border border-dashed border-brand-200 bg-brand-50 p-5 text-sm text-brand-800">
                                {{ __('precios.index.empty_search') }}
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="ss-card">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-semibold text-ink-900">
                                {{ $productoSeleccionado?->nombre_producto ?? __('precios.index.select_product') }}
                            </h2>
                            @if ($productoSeleccionado)
                                <p class="mt-2 text-sm text-ink-500">
                                    {{ collect([$productoSeleccionado->marca, $productoSeleccionado->formato])->filter()->join(' · ') ?: __('precios.index.product_hint') }}
                                </p>
                            @endif
                        </div>
                        @auth
                            @if (auth()->user()?->isAdmin())
                                <a href="{{ route('admin.precios.create') }}" class="ss-btn-green">
                                    <x-ui.icon name="plus" class="h-4 w-4" />
                                    <span>{{ __('precios.index.new') }}</span>
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @forelse ($precios as $precio)
                            @php($esMejor = (float) $precio->precio === (float) $mejorPrecio)
                            <article class="flex min-h-[190px] flex-col justify-between rounded-[15px] bg-white p-5 shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition hover:-translate-y-2">
                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="text-lg font-semibold text-ink-900">{{ $precio->nombre_super }}</h3>
                                        @if ($esMejor)
                                            <span class="rounded-md bg-[var(--color-exito-suave)] px-2 py-1 text-xs font-bold text-brand-600">{{ __('precios.index.best') }}</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-ink-500">{{ $precio->direccion ?? __('common.address_undefined') }}</p>
                                </div>
                                <div class="mt-4 rounded-[10px] bg-[var(--color-superficie-suave)] p-3">
                                    <div class="flex items-center justify-between border-b border-dashed border-[#e0e0e0] pb-2">
                                        <span>{{ __('common.price') }}</span>
                                        <strong class="{{ $esMejor ? 'text-brand-600' : 'text-ink-900' }}">{{ number_format((float) $precio->precio, 2, ',', '.') }} €</strong>
                                    </div>
                                    @if ($precio->precio_unidad !== null)
                                        <p class="mt-2 text-right text-xs text-ink-400">
                                            {{ number_format((float) $precio->precio_unidad, 2, ',', '.') }} € / {{ $precio->unidad_ref ?? __('common.unit_default') }}
                                        </p>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 md:col-span-2">
                                <h3 class="text-xl font-semibold text-ink-900">{{ __('precios.index.empty.title') }}</h3>
                                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('precios.index.empty.text') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
@endsection
