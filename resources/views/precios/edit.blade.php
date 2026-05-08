@extends('layouts.app')

@section('title', 'Editar precio | Admin')

@section('content')
    <section class="flex flex-wrap items-center justify-between gap-6 rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <div>
            <p class="text-sm font-semibold uppercase text-brand-700">Admin</p>
            <h1 class="mt-2 font-display text-4xl text-ink-900">Editar precio</h1>
            <p class="mt-3 text-sm leading-7 text-ink-600">Actualiza precio y referencia por unidad.</p>
        </div>
    </section>

    <section class="mx-auto mt-6 max-w-3xl rounded-lg border border-white/70 bg-white/85 p-6 shadow-soft">
        <form action="{{ route('admin.precios.update', [$precio->id_producto, $precio->id_super]) }}" method="POST" class="grid gap-5">
            @csrf
            @method('PUT')

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="precio">Precio</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="precio" name="precio" step="0.01" min="0" value="{{ old('precio', $precio->precio) }}" required>
                @error('precio')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="precio_unidad">Precio por unidad</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="{{ old('precio_unidad', $precio->precio_unidad) }}">
                @error('precio_unidad')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="grid gap-2">
                <label class="text-sm font-semibold text-ink-700" for="unidad_ref">Unidad referencia</label>
                <input class="rounded-lg border border-brand-100 bg-white px-4 py-3 text-ink-900 focus:border-brand-400 focus:ring-brand-300" type="text" id="unidad_ref" name="unidad_ref" maxlength="20" value="{{ old('unidad_ref', $precio->unidad_ref) }}">
                @error('unidad_ref')
                    <span class="text-sm font-medium text-rose-600">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-full bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-700">Actualizar precio</button>
                <a href="{{ route('precios.index') }}" class="inline-flex items-center rounded-full border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-800 shadow-soft transition hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-800">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
