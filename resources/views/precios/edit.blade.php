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
        <form action="{{ route('admin.precios.update', [$precio->id_producto, $precio->id_super]) }}" method="POST">
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

            <fieldset>
                <label for="moneda">Moneda</label>
                <input type="text" id="moneda" name="moneda" maxlength="3" value="{{ old('moneda', $precio->moneda ?? 'EUR') }}">
                @error('moneda')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="fuente_precio">Fuente precio</label>
                <input type="text" id="fuente_precio" name="fuente_precio" maxlength="50" value="{{ old('fuente_precio', $precio->fuente_precio) }}">
                @error('fuente_precio')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="url_origen">URL origen</label>
                <input type="url" id="url_origen" name="url_origen" maxlength="2048" value="{{ old('url_origen', $precio->url_origen) }}">
                @error('url_origen')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="fecha_precio">Fecha precio</label>
                <input type="date" id="fecha_precio" name="fecha_precio" value="{{ old('fecha_precio', $precio->fecha_precio) }}">
                @error('fecha_precio')
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
