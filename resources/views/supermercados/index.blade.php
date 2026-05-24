@extends('layouts.app')

@section('title', __('supermercados.index.meta_title'))

@section('content')
    @php
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
            <section class="ss-hero mb-8 sm:mb-12">
                <img
                    src="{{ asset('img/encabezados/encabezado_super.PNG') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-white/60" aria-hidden="true"></div>
                <h1 class="ss-hero-title">{{ __('supermercados.index.title') }}</h1>
                <p class="ss-hero-subtitle">
                    {{ __('supermercados.index.subtitle') }}
                </p>
            </section>

            <form id="form-ubicacion-supermercados" method="GET" action="{{ route('supermercados.index') }}" class="mb-8 rounded-[24px] border border-brand-100 bg-white p-4 shadow-[0_10px_30px_rgba(0,0,0,0.05)] sm:p-6">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(280px,0.9fr)]">
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-lg font-semibold text-ink-900 sm:text-xl">{{ __('supermercados.index.location_prompt.title') }}</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">{{ __('supermercados.index.location_prompt.text', ['km' => (int) $radioBusquedaKm]) }}</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="busqueda-super" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('common.search') }}</label>
                                <input
                                    id="busqueda-super"
                                    name="busqueda"
                                    type="search"
                                    value="{{ $busqueda }}"
                                    class="ss-input w-full"
                                    placeholder="{{ __('supermercados.index.search_placeholder') }}"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label for="direccion-postal-super" class="mb-2 block text-sm font-semibold text-ink-800">{{ __('supermercados.index.location_prompt.address_label') }}</label>
                                <input
                                    id="direccion-postal-super"
                                    name="direccion_postal"
                                    type="text"
                                    value="{{ $direccionPostal }}"
                                    class="ss-input w-full"
                                    placeholder="{{ __('supermercados.index.location_prompt.address_placeholder') }}"
                                >
                                <p class="mt-2 text-xs leading-5 text-ink-500">{{ __('supermercados.index.location_prompt.address_hint') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[20px] bg-[linear-gradient(160deg,#f5fbf7_0%,#e9f6ef_100%)] p-4 sm:p-5">
                        <div class="flex h-full flex-col justify-center gap-5">
                            <div class="space-y-3">
                                <input type="hidden" name="latitud" id="supermercados-ubicacion-latitud" value="{{ request('latitud', '') }}">
                                <input type="hidden" name="longitud" id="supermercados-ubicacion-longitud" value="{{ request('longitud', '') }}">
                                <button type="submit" class="ss-btn-green w-full justify-center">{{ __('supermercados.index.location_prompt.address_button') }}</button>
                                <button
                                    type="button"
                                    id="btn-supermercados-usar-ubicacion"
                                    class="ss-btn-outline w-full justify-center whitespace-nowrap"
                                    data-loading-text="{{ __('js.location.loading') }}"
                                    data-error-text="{{ __('js.location.error') }}"
                                >
                                    {{ __('supermercados.index.location_prompt.button') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($mensajeUbicacion)
                    <div class="mt-4 rounded-[16px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $mensajeUbicacion }}
                    </div>
                @endif
            </form>

            @if ($ordenarPorCercania)
                <section class="mb-10 overflow-hidden rounded-[20px] border border-brand-100 bg-white shadow-[0_14px_35px_rgba(0,0,0,0.08)]">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-brand-100 px-5 py-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink-900 sm:text-xl">{{ __('supermercados.index.map_title') }}</h2>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ __('supermercados.index.locations_count', ['count' => $totalSupermercados]) }}
                                · {{ __('supermercados.index.sorted_by_distance') }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700">
                            <x-ui.icon name="map-pin" class="h-4 w-4" />
                            {{ __('supermercados.index.location_active') }}
                        </span>
                    </div>
                    <div
                        id="supermercados-map"
                        class="h-[300px] w-full bg-[#dff2e8] sm:h-[420px]"
                        data-markers='@json($markers->values())'
                        data-user-lat="{{ $latitudUsuario }}"
                        data-user-lng="{{ $longitudUsuario }}"
                        data-empty-title="{{ __('supermercados.index.map_empty.title') }}"
                        data-empty-text="{{ __('supermercados.index.map_empty.text') }}"
                    ></div>
                </section>

                <section class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-ink-900 sm:text-2xl">{{ __('supermercados.index.results_title') }}</h2>
                        <p class="mt-1 text-sm text-ink-600">{{ __('supermercados.index.results_nearest', ['km' => (int) $radioBusquedaKm]) }}</p>
                    </div>
                </section>

                <section class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 sm:gap-8">
                    @forelse ($supermercados as $supermercado)
                        @php
                            $claveLogo = \Illuminate\Support\Str::of($supermercado->nombre_super)->lower()->ascii()->before(' ')->toString();
                            $logo = $logos[$claveLogo] ?? null;
                        @endphp
                        <article class="flex flex-col overflow-hidden rounded-[15px] bg-white shadow-[0_5px_15px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-2 hover:shadow-[0_15px_30px_rgba(0,0,0,0.10)]">
                            <div class="relative flex h-[110px] items-center justify-center bg-[var(--color-superficie-suave)] p-4 sm:h-[120px] sm:p-5">
                                <span class="absolute right-3 top-3 rounded-full bg-brand-700 px-2.5 py-1 text-[11px] font-bold text-white sm:px-3 sm:text-xs">{{ number_format((float) $supermercado->distancia_km, 2, ',', '.') }} km</span>
                                @if ($logo && file_exists(public_path($logo)))
                                    <img src="{{ asset($logo) }}" alt="Logo {{ $supermercado->nombre_super }}" class="max-h-[52px] max-w-[140px] object-contain drop-shadow sm:max-h-[60px] sm:max-w-[160px]" loading="lazy">
                                @else
                                    <span class="text-lg font-semibold text-ink-900 sm:text-xl">{{ $supermercado->nombre_super }}</span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-3 p-4 sm:p-5">
                                <h2 class="text-lg font-semibold text-ink-900 sm:text-xl">{{ $supermercado->nombre_super }}</h2>
                                <p class="text-sm text-ink-600">
                                    <x-ui.icon name="map-pin" class="mr-1 inline h-4 w-4 text-brand-600" />
                                    {{ $supermercado->direccion ?? __('common.address_undefined') }}
                                </p>
                                <a href="{{ route('precios.index') }}" class="ss-btn-outline mt-auto">{{ __('supermercados.index.view_offers') }}</a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[10px] border border-dashed border-brand-200 bg-white p-6 sm:col-span-2 lg:col-span-3">
                            <h2 class="text-xl font-semibold text-ink-900">{{ __('supermercados.index.empty_radius.title') }}</h2>
                            <p class="mt-2 text-sm text-ink-600">{{ __('supermercados.index.empty_radius.text', ['km' => (int) $radioBusquedaKm]) }}</p>
                        </div>
                    @endforelse
                </section>

                <div class="mt-8">
                    {{ $supermercados->links() }}
                </div>
            @else
                <section class="mb-10 rounded-[20px] border border-dashed border-brand-200 bg-white px-6 py-8 shadow-[0_10px_30px_rgba(0,0,0,0.04)]">
                    <h2 class="text-2xl font-semibold text-ink-900">{{ __('supermercados.index.location_required.title') }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">{{ __('supermercados.index.location_required.text', ['km' => (int) $radioBusquedaKm]) }}</p>
                </section>
            @endif

        </div>
    </section>

    @once
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        @endpush
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelectorAll('.js-confirm-delete').forEach((form) => {
                        form.addEventListener('submit', (event) => {
                            if (!window.confirm(form.dataset.confirm ?? '')) {
                                event.preventDefault();
                            }
                        });
                    });

                    const locationForm = document.getElementById('form-ubicacion-supermercados');
                    const locationButton = document.getElementById('btn-supermercados-usar-ubicacion');
                    const latitudInput = document.getElementById('supermercados-ubicacion-latitud');
                    const longitudInput = document.getElementById('supermercados-ubicacion-longitud');
                    const direccionInput = document.getElementById('direccion-postal-super');

                    locationForm?.addEventListener('submit', () => {
                        if ((direccionInput?.value || '').trim() !== '') {
                            latitudInput.value = '';
                            longitudInput.value = '';
                        }
                    });

                    locationButton?.addEventListener('click', () => {
                        if (!navigator.geolocation || !locationForm || !latitudInput || !longitudInput) {
                            return;
                        }

                        locationButton.setAttribute('disabled', 'disabled');
                        locationButton.textContent = locationButton.dataset.loadingText ?? '...';

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                latitudInput.value = String(position.coords.latitude);
                                longitudInput.value = String(position.coords.longitude);
                                locationForm.submit();
                            },
                            () => {
                                locationButton.removeAttribute('disabled');
                                locationButton.textContent = locationButton.dataset.errorText ?? 'Error';
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    });

                    const escapeHtml = (value) => String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#39;');
                    const mapElement = document.getElementById('supermercados-map');

                    if (!mapElement || typeof L === 'undefined') {
                        return;
                    }

                    const markers = JSON.parse(mapElement.dataset.markers || '[]');
                    const userLat = Number.parseFloat(mapElement.dataset.userLat || '');
                    const userLng = Number.parseFloat(mapElement.dataset.userLng || '');
                    const hasUserLocation = Number.isFinite(userLat) && Number.isFinite(userLng);
                    const initialCenter = hasUserLocation
                        ? [userLat, userLng]
                        : (markers[0] ? [markers[0].latitud, markers[0].longitud] : [40.416775, -3.703790]);
                    const map = L.map(mapElement, { scrollWheelZoom: false }).setView(initialCenter, hasUserLocation ? 13 : 6);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    const bounds = [];

                    if (hasUserLocation) {
                        L.circleMarker([userLat, userLng], {
                            radius: 9,
                            color: '#146546',
                            fillColor: '#14b86a',
                            fillOpacity: 0.95,
                            weight: 3,
                        }).addTo(map).bindPopup('{{ __('supermercados.index.user_location_popup') }}');
                        bounds.push([userLat, userLng]);
                    }

                    markers.forEach((marker) => {
                        const popup = `<strong>${escapeHtml(marker.nombre)}</strong>${marker.direccion ? `<br>${escapeHtml(marker.direccion)}` : ''}`;
                        L.marker([marker.latitud, marker.longitud]).addTo(map).bindPopup(popup);
                        bounds.push([marker.latitud, marker.longitud]);
                    });

                    if (bounds.length > 1) {
                        map.fitBounds(bounds, { padding: [28, 28], maxZoom: hasUserLocation ? 14 : 7 });
                    }
                });
            </script>
        @endpush
    @endonce
@endsection
