@extends('layouts.app')

@section('title', __('productos_externos.index.meta_title'))

@section('content')
    <section class="ss-section">
        <div class="ss-container space-y-6">
            <section class="ss-header-gradient flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 p-6 shadow-soft">
                <div>
                    <p class="text-sm font-semibold uppercase text-brand-700">{{ __('common.admin') }}</p>
                    <h1 class="mt-2 font-display text-4xl text-ink-900">{{ __('productos_externos.index.title') }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-600">{{ __('productos_externos.index.subtitle') }}</p>
                </div>
                <a class="inline-flex items-center rounded-full border border-brand-200 bg-white px-4 py-2.5 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50" href="{{ route('admin.index', ['tab' => 'productos']) }}">{{ __('productos_externos.index.back_to_products') }}</a>
            </section>

        <section class="grid gap-4 md:grid-cols-4">
        @foreach ($resumenEstados as $estado => $total)
            <article class="rounded-lg border border-white/70 bg-white/85 p-5 shadow-soft">
                <p class="text-sm font-semibold uppercase tracking-[0.12em] text-ink-500">{{ __('productos_externos.states.' . $estado) }}</p>
                <p class="mt-3 text-3xl font-semibold text-ink-900">{{ $total }}</p>
                <div class="mt-4 h-1 w-20 rounded-full bg-brand-100"></div>
            </article>
        @endforeach
        </section>

        <section class="rounded-lg border border-white/70 bg-white/85 p-5 shadow-soft">
            <form method="GET" action="{{ route('admin.productos-externos.index') }}" class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr_0.8fr_auto]">
            <label class="grid gap-2">
                <span class="text-sm font-semibold uppercase tracking-[0.12em] text-ink-500">{{ __('common.search') }}</span>
                <input
                    type="search"
                    name="busqueda"
                    value="{{ $busqueda }}"
                    class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300"
                    placeholder="{{ __('productos_externos.index.search_placeholder') }}"
                >
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-semibold uppercase tracking-[0.12em] text-ink-500">{{ __('productos_externos.index.all_sources') }}</span>
                <select name="fuente" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300">
                    <option value="">{{ __('productos_externos.index.all_sources') }}</option>
                    @foreach ($fuentesDisponibles as $fuente)
                        <option value="{{ $fuente }}" {{ $filtroFuente === $fuente ? 'selected' : '' }}>{{ ucfirst($fuente) }}</option>
                    @endforeach
                </select>
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-semibold uppercase tracking-[0.12em] text-ink-500">{{ __('productos_externos.index.unresolved_only') }}</span>
                <select name="estado" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300">
                    <option value="">{{ __('productos_externos.index.unresolved_only') }}</option>
                    @foreach ($estadosDisponibles as $estado)
                        <option value="{{ $estado }}" {{ $filtroEstado === $estado ? 'selected' : '' }}>{{ __('productos_externos.states.' . $estado) }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap items-end gap-3">
                <button type="submit" class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">{{ __('common.search') }}</button>
                <a href="{{ route('admin.productos-externos.index') }}" class="inline-flex items-center rounded-full border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 shadow-soft transition hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800">{{ __('productos_externos.index.clear_filters') }}</a>
            </div>
            </form>
        </section>

        <section class="grid gap-5">
        @forelse ($productosExternos as $productoExterno)
            @php
                $snapshot = $productoExterno->sugerencia_snapshot ?? [];
                $estado = $productoExterno->mapeo_estado;
                $badgeClass = match ($estado) {
                    'mapeado' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    'sugerido' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'descartado' => 'border-rose-200 bg-rose-50 text-rose-700',
                    default => 'border-slate-200 bg-slate-50 text-slate-700',
                };
                $candidatosFila = $candidatos[$productoExterno->id] ?? collect();
            @endphp

            <article class="rounded-lg border border-white/70 bg-white/90 p-6 shadow-soft">
                <div class="flex flex-wrap items-start justify-between gap-5">
                    <div class="max-w-3xl">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full border px-3 py-1 text-sm font-semibold {{ $badgeClass }}">{{ __('productos_externos.states.' . $estado) }}</span>
                            <span class="text-sm text-ink-500">{{ ucfirst($productoExterno->fuente) }} · ID {{ $productoExterno->external_id }}</span>
                            @if ($productoExterno->sugerencia_score !== null)
                                <span class="text-sm text-ink-500">{{ __('productos_externos.index.score') }} {{ number_format((float) $productoExterno->sugerencia_score, 4, ',', '.') }}</span>
                            @endif
                        </div>

                        <h2 class="mt-4 text-2xl font-semibold leading-tight text-ink-900">{{ $productoExterno->nombre ?? __('common.product') }}</h2>
                        <p class="mt-2 text-sm text-ink-600">{{ collect([$productoExterno->marca, $productoExterno->formato, $productoExterno->tamano])->filter()->join(' · ') ?: __('productos_externos.index.no_format') }}</p>
                        <p class="mt-2 text-sm text-ink-500">{{ __('productos_externos.index.category') }} {{ $productoExterno->categoria_nombre ?? __('common.no') }}</p>

                        @if ($productoExterno->producto)
                            <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-800">
                                <strong>{{ __('productos_externos.index.linked_product') }}</strong>
                                <span class="block mt-1">{{ $productoExterno->producto->nombre_producto }}{{ $productoExterno->producto->marca ? ' · ' . $productoExterno->producto->marca : '' }}</span>
                            </div>
                        @elseif (! empty($snapshot))
                            <div class="mt-4 rounded-lg border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800">
                                <strong>{{ __('productos_externos.index.suggested_product') }}</strong>
                                <span class="mt-1 block">{{ $snapshot['nombre_producto'] ?? '' }}{{ ! empty($snapshot['marca']) ? ' · ' . $snapshot['marca'] : '' }}{{ ! empty($snapshot['formato']) ? ' · ' . $snapshot['formato'] : '' }}</span>
                                @if (! empty($snapshot['seccion']))
                                    <span class="mt-1 block text-xs uppercase tracking-[0.12em]">{{ $snapshot['seccion'] }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="w-full max-w-xl space-y-4">
                        <form method="GET" action="{{ route('admin.productos-externos.index') }}" class="grid gap-3 rounded-lg border border-brand-100 bg-mist/40 p-4 md:grid-cols-[1fr_auto]">
                            <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                            <input type="hidden" name="fuente" value="{{ $filtroFuente }}">
                            <input type="hidden" name="estado" value="{{ $filtroEstado }}">
                            <input type="hidden" name="externo" value="{{ $productoExterno->id }}">
                            <input
                                type="search"
                                name="busqueda_producto"
                                value="{{ $externoBuscado === $productoExterno->id ? $busquedaProducto : '' }}"
                                class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300"
                                placeholder="{{ __('productos_externos.index.search_internal_placeholder') }}"
                            >
                            <button type="submit" class="inline-flex items-center justify-center rounded-full border border-brand-200 bg-white px-5 py-3 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50">{{ __('common.search') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.productos-externos.confirmar', $productoExterno) }}" class="grid gap-3 rounded-lg border border-brand-100 bg-white p-4 md:grid-cols-[1fr_auto]">
                            @csrf
                            <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                            <input type="hidden" name="fuente" value="{{ $filtroFuente }}">
                            <input type="hidden" name="estado" value="{{ $filtroEstado }}">
                            <input type="hidden" name="externo" value="{{ $externoBuscado }}">
                            <input type="hidden" name="busqueda_producto" value="{{ $externoBuscado === $productoExterno->id ? $busquedaProducto : '' }}">

                            <select name="producto_id" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" required>
                                <option value="">{{ __('productos_externos.index.select_candidate') }}</option>
                                @foreach ($candidatosFila as $candidato)
                                    <option value="{{ $candidato['producto']->id }}" {{ (string) ($snapshot['id'] ?? '') === (string) $candidato['producto']->id ? 'selected' : '' }}>
                                        {{ $candidato['producto']->nombre_producto }}{{ $candidato['producto']->marca ? ' · ' . $candidato['producto']->marca : '' }}{{ $candidato['producto']->formato ? ' · ' . $candidato['producto']->formato : '' }} · {{ number_format($candidato['score'], 4, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">{{ __('productos_externos.index.confirm_mapping') }}</button>
                        </form>
                        @error('producto_id')
                            <span class="block text-sm font-medium text-rose-600">{{ $message }}</span>
                        @enderror

                        <form method="POST" action="{{ route('admin.productos-externos.store', $productoExterno) }}" class="grid gap-3 rounded-lg border border-brand-100 bg-mist/70 p-4 md:grid-cols-2">
                            @csrf
                            <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                            <input type="hidden" name="fuente" value="{{ $filtroFuente }}">
                            <input type="hidden" name="estado" value="{{ $filtroEstado }}">
                            <input type="hidden" name="externo" value="{{ $externoBuscado }}">
                            <input type="hidden" name="busqueda_producto" value="{{ $externoBuscado === $productoExterno->id ? $busquedaProducto : '' }}">

                            <fieldset class="grid gap-2 md:col-span-2">
                                <label class="text-sm font-semibold text-ink-700" for="nombre_producto_{{ $productoExterno->id }}">{{ __('productos.form.name') }}</label>
                                <input id="nombre_producto_{{ $productoExterno->id }}" name="nombre_producto" type="text" value="{{ old('nombre_producto', $productoExterno->nombre) }}" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" required>
                            </fieldset>

                            <fieldset class="grid gap-2">
                                <label class="text-sm font-semibold text-ink-700" for="id_seccion_{{ $productoExterno->id }}">{{ __('productos.form.section') }}</label>
                                <select id="id_seccion_{{ $productoExterno->id }}" name="id_seccion" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" required>
                                    <option value="">{{ __('productos.form.section_placeholder') }}</option>
                                    @foreach ($secciones as $seccion)
                                        <option value="{{ $seccion->id }}" {{ old('id_seccion') == $seccion->id ? 'selected' : '' }}>{{ $seccion->nombre_seccion }}</option>
                                    @endforeach
                                </select>
                            </fieldset>

                            <fieldset class="grid gap-2">
                                <label class="text-sm font-semibold text-ink-700" for="marca_{{ $productoExterno->id }}">{{ __('productos.form.brand') }}</label>
                                <input id="marca_{{ $productoExterno->id }}" name="marca" type="text" value="{{ old('marca', $productoExterno->marca) }}" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300">
                            </fieldset>

                            <fieldset class="grid gap-2">
                                <label class="text-sm font-semibold text-ink-700" for="formato_{{ $productoExterno->id }}">{{ __('productos.form.format') }}</label>
                                <input id="formato_{{ $productoExterno->id }}" name="formato" type="text" value="{{ old('formato', trim(collect([$productoExterno->formato, $productoExterno->tamano])->filter()->implode(' '))) }}" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300">
                            </fieldset>

                            <fieldset class="grid gap-2">
                                <label class="text-sm font-semibold text-ink-700" for="imagen_{{ $productoExterno->id }}">{{ __('productos.form.image') }}</label>
                                <input id="imagen_{{ $productoExterno->id }}" name="imagen" type="text" value="{{ old('imagen', $productoExterno->imagen) }}" class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300">
                            </fieldset>

                            <div class="md:col-span-2 flex flex-wrap gap-3">
                                <button type="submit" class="inline-flex items-center rounded-full border border-brand-200 bg-white px-5 py-3 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50">{{ __('productos_externos.index.create_and_map') }}</button>
                                <button type="submit" form="descartar-{{ $productoExterno->id }}" class="inline-flex items-center rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-rose-500">{{ __('productos_externos.index.discard') }}</button>
                            </div>
                        </form>

                        <form id="descartar-{{ $productoExterno->id }}" method="POST" action="{{ route('admin.productos-externos.descartar', $productoExterno) }}">
                            @csrf
                            <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                            <input type="hidden" name="fuente" value="{{ $filtroFuente }}">
                            <input type="hidden" name="estado" value="{{ $filtroEstado }}">
                            <input type="hidden" name="externo" value="{{ $externoBuscado }}">
                            <input type="hidden" name="busqueda_producto" value="{{ $externoBuscado === $productoExterno->id ? $busquedaProducto : '' }}">
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-brand-200 bg-white p-6 text-center">
                <h2 class="text-xl font-semibold text-ink-900">{{ __('productos_externos.index.empty.title') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('productos_externos.index.empty.text') }}</p>
            </div>
        @endforelse
        </section>

            <div class="rounded-lg border border-white/70 bg-white/80 p-4 shadow-soft">
                {{ $productosExternos->links() }}
            </div>
        </div>
    </section>
@endsection
