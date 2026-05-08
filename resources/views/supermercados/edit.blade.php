@extends('layouts.app')

@section('title', __('supermercados.edit.meta_title'))

@section('content')
    <section class="flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('common.admin') }}</p>
            <h1 class="mt-2 font-display text-4xl text-ink-900">{{ __('supermercados.edit.title') }}</h1>
            <p class="mt-3 text-sm leading-7 text-ink-600">{{ __('supermercados.edit.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto mt-6 max-w-3xl rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <form action="{{ route('admin.supermercados.update', $supermercado) }}" method="POST" class="grid gap-5">
            @csrf
            @method('PUT')

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="nombre_super">{{ __('supermercados.form.name') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="nombre_super" name="nombre_super" value="{{ old('nombre_super', $supermercado->nombre_super) }}" required>
                @error('nombre_super')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="latitud">{{ __('common.latitude') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="latitud" name="latitud" step="0.000001" value="{{ old('latitud', $supermercado->latitud) }}">
                @error('latitud')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="longitud">{{ __('common.longitude') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="longitud" name="longitud" step="0.000001" value="{{ old('longitud', $supermercado->longitud) }}">
                @error('longitud')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">{{ __('supermercados.edit.submit') }}</button>
                <a href="{{ route('supermercados.index') }}" class="inline-flex items-center rounded-full border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 shadow-soft transition hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
