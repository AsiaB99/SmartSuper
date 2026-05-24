@extends('layouts.app')

@section('title', __('productos.create.meta_title'))

@section('content')
    <section class="flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">{{ __('common.admin') }}</p>
            <h1 class="mt-2 font-display text-4xl text-ink-900">{{ __('productos.create.title') }}</h1>
            <p class="mt-3 text-sm leading-7 text-ink-600">{{ __('productos.create.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto mt-6 max-w-3xl rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <form action="{{ route('admin.productos.store') }}" method="POST" class="grid gap-5">
            @csrf

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="nombre_producto">{{ __('productos.form.name') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="nombre_producto" name="nombre_producto" value="{{ old('nombre_producto') }}" required>
                @error('nombre_producto')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="id_seccion">{{ __('productos.form.section') }}</label>
                <select class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" id="id_seccion" name="id_seccion" required>
                    <option value="">{{ __('productos.form.section_placeholder') }}</option>
                    @foreach ($secciones as $seccion)
                        <option value="{{ $seccion->id }}" {{ old('id_seccion') == $seccion->id ? 'selected' : '' }}>
                            {{ $seccion->nombre_seccion }}
                        </option>
                    @endforeach
                </select>
                @error('id_seccion')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="marca">{{ __('productos.form.brand') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="marca" name="marca" value="{{ old('marca') }}">
                @error('marca')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="formato">{{ __('productos.form.format') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="formato" name="formato" value="{{ old('formato') }}">
                @error('formato')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="imagen">{{ __('productos.form.image') }}</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="imagen" name="imagen" value="{{ old('imagen') }}">
                @error('imagen')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">{{ __('productos.create.submit') }}</button>
                <a href="{{ route('productos.index') }}" class="inline-flex items-center rounded-full border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 shadow-soft transition hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
