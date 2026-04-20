@extends('layouts.app')

@section('title', 'Editar precio | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Editar precio</h1>
            <p class="hero-copy">Actualiza precio y referencia por unidad.</p>
        </div>
    </section>

    <section class="panel-card">
        <form action="{{ route('precios.update', [$precio->id_producto, $precio->id_super]) }}" method="POST">
            @csrf
            @method('PUT')

            <fieldset>
                <label for="precio">Precio</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="{{ old('precio', $precio->precio) }}" required>
                @error('precio')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="precio_unidad">Precio por unidad</label>
                <input type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="{{ old('precio_unidad', $precio->precio_unidad) }}">
                @error('precio_unidad')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="unidad_ref">Unidad referencia</label>
                <input type="text" id="unidad_ref" name="unidad_ref" maxlength="20" value="{{ old('unidad_ref', $precio->unidad_ref) }}">
                @error('unidad_ref')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Actualizar precio</button>
                <a href="{{ route('precios.index') }}" class="button">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
