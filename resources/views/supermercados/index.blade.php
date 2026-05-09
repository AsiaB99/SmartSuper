@extends('layouts.app')

@section('title', __('supermercados.index.meta_title'))

@section('content')
    @php
        $esAdmin = auth()->user()?->isAdmin() ?? false;
        $logos = [
            'mercadona' => 'img/supermercados/mercadona.png',
            'carrefour' => 'img/supermercados/carrefour.png',
            'lidl' => 'img/supermercados/lidl.png',
            'dia' => 'img/supermercados/dia.png',
            'aldi' => 'img/supermercados/aldi.png',
            'consum' => 'img/supermercados/consum.png',
            'alcampo' => 'img/supermercados/alcampo.png',
            'supercor' => 'img/supermercados/supercor.png',
        ];
    @endphp

    <section class="ss-section">
        <div class="ss-container">
            <section class="relative mb-12 overflow-hidden rounded-[20px] p-10 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <img
                    src="{{ asset('img/encabezados/encabezado_super.PNG') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-white/60" aria-hidden="true"></div>
                <h1 class="relative text-4xl font-semibold text-ink-900">{{ __('supermercados.index.title') }}</h1>
                <p class="relative mx-auto mt-4 max-w-3xl text-lg leading-7 text-ink-600">
                    {{ __('supermercados.index.subtitle') }}
                </p>
            </section>

            <form method="GET" action="{{ route('supermercados.index') }}" class="mb-8 flex flex-wrap gap-3 rounded-[20px] bg-white p-5 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <input
                    id="busqueda-super"
                    name="busqueda"
                    type="search"
                    value="{{ $busqueda }}"
                    class="ss-input min-w-[260px] flex-1"
                    placeholder="{{ __('supermercados.index.search_placeholder') }}"
                >
                <button type="submit" class="ss-btn-green">{{ __('common.search') }}</button>
            </form>

            <section class="relative mb-10 h-[400px] w-full overflow-hidden rounded-[20px] border-4 border-white bg-[#dff2e8] shadow-[0_10px_30px_rgba(0,0,0,0.10)]">
                <div class="absolute inset-0 opacity-80 [background-image:linear-gradient(90deg,rgba(20,101,70,.14)_1px,transparent_1px),linear-gradient(rgba(20,101,70,.14)_1px,transparent_1px)] [background-size:44px_44px]"></div>
                <div class="absolute left-0 top-1/3 h-10 w-full rotate-[-8deg] bg-white/70 shadow-soft"></div>
                <div class="absolute left-1/4 top-0 h-full w-10 rotate-[12deg] bg-white/60 shadow-soft"></div>
                <div class="absolute bottom-5 left-5 rounded-[10px] bg-white/95 px-4 py-3 shadow-soft">
                    <p class="text-sm font-semibold text-brand-600">{{ __('supermercados.index.locations_count', ['count' => $supermercadosMapa->count()]) }}</p>
                </div>
                @forelse ($markers as $marker)
                    <div
                        class="js-map-marker absolute z-10 -translate-x-1/2 -translate-y-1/2"
                        data-top="{{ $marker['top'] }}"
                        data-left="{{ $marker['left'] }}"
                        title="{{ $marker['nombre'] }}"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-accent-500 text-white shadow-soft ring-4 ring-white">
                            <x-ui.icon name="map-pin" class="h-5 w-5" />
                        </div>
                    </div>
                @empty
                    <div class="absolute inset-0 flex items-center justify-center p-8 text-center">
                        <div class="rounded-[10px] bg-white/95 px-6 py-5 shadow-soft">
                            <h2 class="text-lg font-semibold text-ink-900">{{ __('supermercados.index.map_empty.title') }}</h2>
                            <p class="mt-2 text-sm text-ink-600">{{ __('supermercados.index.map_empty.text') }}</p>
                        </div>
                    </div>
                @endforelse
            </section>

            <section class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($supermercadosMapa as $supermercado)
                    @php
                        $claveLogo = \Illuminate\Support\Str::of($supermercado->nombre_super)->lower()->ascii()->before(' ')->toString();
                        $logo = $logos[$claveLogo] ?? null;
                    @endphp
                    <article class="flex flex-col overflow-hidden rounded-[15px] bg-white shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-2 hover:shadow-[0_15px_30px_rgba(0,0,0,0.10)]">
                        <div class="relative flex h-[120px] items-center justify-center bg-[var(--color-superficie-suave)] p-5">
                            <span class="absolute right-3 top-3 rounded-full bg-black/70 px-3 py-1 text-xs font-bold text-white">{{ __('supermercados.index.near') }}</span>
                            @if ($logo && file_exists(public_path($logo)))
                                <img src="{{ asset($logo) }}" alt="Logo {{ $supermercado->nombre_super }}" class="max-h-[60px] max-w-[160px] object-contain drop-shadow" loading="lazy">
                            @else
                                <span class="text-xl font-semibold text-ink-900">{{ $supermercado->nombre_super }}</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-5">
                            <h2 class="text-xl font-semibold text-ink-900">{{ $supermercado->nombre_super }}</h2>
                            <p class="text-sm text-ink-600">
                                <x-ui.icon name="map-pin" class="mr-1 inline h-4 w-4 text-brand-600" />
                                {{ $supermercado->direccion ?? __('common.address_undefined') }}
                            </p>
                            <a href="{{ route('precios.index') }}" class="ss-btn-outline mt-auto">{{ __('supermercados.index.view_offers') }}</a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 sm:col-span-2 lg:col-span-3">
                        <h2 class="text-xl font-semibold text-ink-900">{{ __('supermercados.index.empty_search') }}</h2>
                    </div>
                @endforelse
            </section>

            @if ($esAdmin)
                <section class="mt-10 grid gap-4 rounded-[15px] bg-white p-5 shadow-[0_5px_15px_rgba(0,0,0,0.05)]">
                    @forelse ($supermercados as $supermercado)
                        <article class="flex flex-wrap items-center justify-between gap-4 rounded-[10px] border border-[var(--color-borde-suave)] bg-[var(--color-superficie-suave)] p-5">
                            <div>
                                <h2 class="text-xl font-semibold text-ink-900">{{ $supermercado->nombre_super }}</h2>
                                <p class="mt-2 text-sm text-ink-600">{{ __('common.latitude') }}: {{ $supermercado->latitud ?? __('common.no_date_f') }}</p>
                                <p class="text-sm text-ink-600">{{ __('common.longitude') }}: {{ $supermercado->longitud ?? __('common.no_date_f') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="{{ route('admin.supermercados.edit', $supermercado) }}" class="ss-btn-outline">{{ __('common.edit') }}</a>
                                <form action="{{ route('admin.supermercados.destroy', $supermercado) }}" method="POST" class="js-confirm-delete" data-confirm="{{ __('supermercados.index.delete_confirm') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-[10px] bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">{{ __('common.delete') }}</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6">
                            <h2 class="text-xl font-semibold text-ink-900">{{ __('supermercados.index.empty.title') }}</h2>
                        </div>
                    @endforelse
                    {{ $supermercados->links() }}
                </section>
            @endif
        </div>
    </section>

    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.js-map-marker').forEach((marker) => {
                        marker.style.top = `${marker.dataset.top}%`;
                        marker.style.left = `${marker.dataset.left}%`;
                    });

                    document.querySelectorAll('.js-confirm-delete').forEach((form) => {
                        form.addEventListener('submit', (event) => {
                            if (!window.confirm(form.dataset.confirm ?? '')) {
                                event.preventDefault();
                            }
                        });
                    });
                });
            </script>
        @endpush
    @endonce
@endsection

