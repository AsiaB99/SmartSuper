@extends('layouts.app')

@section('title', __('dashboard.meta_title'))

@section('content')
    @php
        $features = [
            ['icon' => 'magnifying-glass', 'title' => __('dashboard.features.compare.title'), 'text' => __('dashboard.features.compare.text')],
            ['icon' => 'list-bullet', 'title' => __('dashboard.features.lists.title'), 'text' => __('dashboard.features.lists.text')],
            ['icon' => 'map-pin', 'title' => __('dashboard.features.location.title'), 'text' => __('dashboard.features.location.text')],
            ['icon' => 'archive-box', 'title' => __('dashboard.features.pantry.title'), 'text' => __('dashboard.features.pantry.text')],
        ];

        $supermercados = [
            ['nombre' => 'Mercadona', 'logo' => 'img/supermercados/mercadona.png'],
            ['nombre' => 'Carrefour', 'logo' => 'img/supermercados/carrefour.png'],
            ['nombre' => 'Lidl', 'logo' => 'img/supermercados/lidl.png'],
            ['nombre' => 'Dia', 'logo' => 'img/supermercados/dia.png'],
            ['nombre' => 'Aldi', 'logo' => 'img/supermercados/aldi.png'],
            ['nombre' => 'Consum', 'logo' => 'img/supermercados/consum.png'],
            ['nombre' => 'Alcampo', 'logo' => 'img/supermercados/alcampo.png'],
            ['nombre' => 'Supercor', 'logo' => 'img/supermercados/supercor.png'],
        ];
    @endphp

    <section id="inicio" class="relative flex min-h-[68vh] items-center justify-center overflow-hidden px-4 py-12 text-center text-white sm:min-h-[80vh] sm:px-5">
        <img
            src="{{ asset('img/background-index.webp') }}"
            alt=""
            class="absolute inset-0 h-full w-full object-cover brightness-[0.82]"
            loading="eager"
            fetchpriority="high"
            decoding="async"
            aria-hidden="true"
        >
        <div class="absolute inset-0 bg-black/55" aria-hidden="true"></div>

        <div class="relative max-w-4xl">
            <h1 class="font-display text-3xl font-semibold leading-tight sm:text-5xl lg:text-6xl">{{ __('dashboard.hero.title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 sm:mt-5 sm:text-xl sm:leading-8">
                {{ __('dashboard.hero.subtitle') }}
            </p>
            <a href="{{ route('listas.index') }}" class="ss-btn-primary mt-6 sm:mt-8">
                <x-ui.icon name="list-bullet" class="h-5 w-5" />
                <span>{{ __('dashboard.hero.cta') }}</span>
            </a>
        </div>
    </section>

    <section id="features" class="ss-section bg-white">
        <div class="ss-container">
            <h2 class="ss-title">{{ __('dashboard.features.title') }}</h2>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($features as $feature)
                    <article class="ss-card ss-card-hover text-center">
                        <div class="ss-icon-bubble mb-4 h-20 w-20 sm:mb-5 sm:h-28 sm:w-28">
                            <x-ui.icon :name="$feature['icon']" class="h-16 w-16 sm:h-24 sm:w-24" />
                        </div>
                        <h3 class="text-lg font-semibold text-ink-900 sm:text-xl">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-sm leading-6 text-ink-600 sm:mt-4 sm:leading-7">{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="supermercados" class="ss-section overflow-hidden bg-fondo-claro">
        <h2 class="ss-title">{{ __('dashboard.supermarkets.title') }}</h2>

        <div class="mt-8 overflow-hidden py-3 sm:mt-10 sm:py-5">
            <div class="flex w-max animate-[smart-scroll_30s_linear_infinite] gap-3 px-4 sm:gap-5 sm:px-5">
                @foreach (array_merge($supermercados, $supermercados) as $supermercado)
                    <article class="flex h-[96px] w-[190px] shrink-0 items-center justify-center rounded-[10px] bg-white p-4 shadow-[0_4px_10px_rgba(0,0,0,0.10)] transition duration-300 hover:scale-105 sm:h-[120px] sm:w-[250px] sm:p-5">
                        @if (file_exists(public_path($supermercado['logo'])))
                            <img
                                src="{{ asset($supermercado['logo']) }}"
                                alt="Logo {{ $supermercado['nombre'] }}"
                                class="max-h-[80%] max-w-[80%] object-contain"
                                loading="lazy"
                            >
                        @else
                            <span class="text-center text-lg font-semibold text-ink-800">
                                {{ $supermercado['nombre'] }}
                            </span>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @auth
        <section class="ss-section bg-white">
            <div class="ss-container grid gap-5 md:grid-cols-3">
                <a href="{{ route('listas.index') }}" class="ss-card ss-card-hover">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.list.label') }}</p>
                    <h2 class="mt-3 text-lg font-semibold text-ink-900 sm:text-xl">{{ __('dashboard.auth.list.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600 sm:leading-7">{{ __('dashboard.auth.list.text') }}</p>
                </a>
                @if (! auth()->user()?->isAdmin())
                    <a href="{{ route('despensas.index') }}" class="ss-card ss-card-hover">
                        <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.pantry.label') }}</p>
                        <h2 class="mt-3 text-lg font-semibold text-ink-900 sm:text-xl">{{ __('dashboard.auth.pantry.title') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-ink-600 sm:leading-7">{{ __('dashboard.auth.pantry.text') }}</p>
                    </a>
                @endif
                <a href="{{ route('precios.index') }}" class="ss-card ss-card-hover">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.comparator.label') }}</p>
                    <h2 class="mt-3 text-lg font-semibold text-ink-900 sm:text-xl">{{ __('dashboard.auth.comparator.title') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-600 sm:leading-7">{{ __('dashboard.auth.comparator.text') }}</p>
                </a>
            </div>
        </section>
    @endauth
@endsection
