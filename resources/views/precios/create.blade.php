@extends('layouts.app')

@section('title', __('precios.create.meta_title'))

@section('content')
    <section class="flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('common.admin') }}</p>
            <h1 class="mt-2 font-display text-4xl text-ink-900">{{ __('precios.create.title') }}</h1>
            <p class="mt-3 text-sm leading-7 text-ink-600">{{ __('precios.create.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto mt-6 max-w-3xl rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <form action="{{ route('admin.precios.store') }}" method="POST" class="grid gap-5">
            @csrf

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="id_producto">{{ __('common.product') }}</label>
                <select class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" id="id_producto" name="id_producto" required>
                    <option value="">{{ __('precios.form.product_placeholder') }}</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" {{ old('id_producto') == $producto->id ? 'selected' : '' }}>
                            {{ $producto->nombre_producto }}
                        </option>
                    @endforeach
                </select>
                @error('id_producto')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="id_super">{{ __('common.supermarket') }}</label>
                <select class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" id="id_super" name="id_super" required>
                    <option value="">{{ __('precios.form.supermarket_placeholder') }}</option>
                    @foreach ($supermercados as $supermercado)
                        <option value="{{ $supermercado->id }}" {{ old('id_super') == $supermercado->id ? 'selected' : '' }}>
                            {{ $supermercado->nombre_super }}
                        </option>
                    @endforeach
                </select>
                @error('id_super')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="precio">{{ __('common.price') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="precio" name="precio" step="0.01" min="0" value="{{ old('precio') }}" required>
                @error('precio')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="precio_unidad">{{ __('listas.recommendation.unit_price') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="{{ old('precio_unidad') }}">
                @error('precio_unidad')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="unidad_ref">{{ __('common.unit_reference') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="unidad_ref" name="unidad_ref" maxlength="20" value="{{ old('unidad_ref') }}" placeholder="{{ __('precios.form.unit_reference_placeholder') }}">
                @error('unidad_ref')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">{{ __('precios.create.submit') }}</button>
                <a href="{{ route('precios.index') }}" class="inline-flex items-center rounded-full border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 shadow-soft transition hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
