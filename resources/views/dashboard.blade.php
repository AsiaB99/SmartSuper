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

    <section id="inicio" class="flex min-h-[80vh] items-center justify-center bg-[linear-gradient(rgba(0,0,0,0.50),rgba(0,0,0,0.50)),url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center px-5 text-center text-white">
        <div class="max-w-4xl">
            <h1 class="font-display text-4xl font-semibold leading-tight sm:text-5xl lg:text-6xl">{{ __('dashboard.hero.title') }}</h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-8 sm:text-xl">
                {{ __('dashboard.hero.subtitle') }}
            </p>
            <a href="{{ route('listas.index') }}" class="ss-btn-primary mt-8">
                <x-ui.icon name="list-bullet" class="h-5 w-5" />
                <span>{{ __('dashboard.hero.cta') }}</span>
            </a>
        </div>
    </section>

    <section id="features" class="ss-section bg-white">
        <div class="ss-container">
            <h2 class="ss-title">{{ __('dashboard.features.title') }}</h2>

            <div class="mt-10 grid gap-8 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($features as $feature)
                    <article class="ss-card ss-card-hover text-center">
                        <div class="ss-icon-bubble">
                            <x-ui.icon :name="$feature['icon']" class="h-24 w-24" />
                        </div>
                        <h3 class="text-xl font-semibold text-ink-900">{{ $feature['title'] }}</h3>
                        <p class="mt-4 text-sm leading-7 text-ink-600">{{ $feature['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="supermercados" class="ss-section overflow-hidden bg-fondo-claro">
        <h2 class="ss-title">{{ __('dashboard.supermarkets.title') }}</h2>

        <div class="mt-10 overflow-hidden py-5">
            <div class="flex w-max animate-[smart-scroll_30s_linear_infinite] gap-5 px-5">
                @foreach (array_merge($supermercados, $supermercados) as $supermercado)
                    <article class="flex h-[120px] w-[250px] shrink-0 items-center justify-center rounded-[10px] bg-white p-5 shadow-[0_4px_10px_rgba(0,0,0,0.10)] transition duration-300 hover:scale-105">
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
            <div class="ss-container grid gap-6 md:grid-cols-3">
                <a href="{{ route('listas.index') }}" class="ss-card ss-card-hover">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.list.label') }}</p>
                    <h2 class="mt-3 text-xl font-semibold text-ink-900">{{ __('dashboard.auth.list.title') }}</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('dashboard.auth.list.text') }}</p>
                </a>
                <a href="{{ route('despensas.index') }}" class="ss-card ss-card-hover">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.pantry.label') }}</p>
                    <h2 class="mt-3 text-xl font-semibold text-ink-900">{{ __('dashboard.auth.pantry.title') }}</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('dashboard.auth.pantry.text') }}</p>
                </a>
                <a href="{{ route('precios.index') }}" class="ss-card ss-card-hover">
                    <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-600">{{ __('dashboard.auth.comparator.label') }}</p>
                    <h2 class="mt-3 text-xl font-semibold text-ink-900">{{ __('dashboard.auth.comparator.title') }}</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('dashboard.auth.comparator.text') }}</p>
                </a>
            </div>
        </section>
    @endauth
@endsection
