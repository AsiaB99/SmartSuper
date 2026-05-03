@extends('layouts.app')

@section('title', 'Nuevo precio | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Crear precio</h1>
            <p class="hero-copy">Asigna producto, supermercado y precio base.</p>
        </div>
    </section>

    <section class="panel-card">
        <form action="{{ route('admin.precios.store') }}" method="POST">
            @csrf

            <fieldset>
                <label for="id_producto">Producto</label>
                <select id="id_producto" name="id_producto" required>
                    <option value="">-- Selecciona producto --</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" {{ old('id_producto') == $producto->id ? 'selected' : '' }}>
                            {{ $producto->nombre_producto }}
                        </option>
                    @endforeach
                </select>
                @error('id_producto')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="id_super">Supermercado</label>
                <select id="id_super" name="id_super" required>
                    <option value="">-- Selecciona supermercado --</option>
                    @foreach ($supermercados as $supermercado)
                        <option value="{{ $supermercado->id }}" {{ old('id_super') == $supermercado->id ? 'selected' : '' }}>
                            {{ $supermercado->nombre_super }}
                        </option>
                    @endforeach
                </select>
                @error('id_super')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="precio">Precio</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0" value="{{ old('precio') }}" required>
                @error('precio')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="precio_unidad">Precio por unidad</label>
                <input type="number" id="precio_unidad" name="precio_unidad" step="0.01" min="0" value="{{ old('precio_unidad') }}">
                @error('precio_unidad')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="unidad_ref">Unidad referencia</label>
                <input type="text" id="unidad_ref" name="unidad_ref" maxlength="20" value="{{ old('unidad_ref') }}" placeholder="kg, litro, unidad...">
                @error('unidad_ref')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="moneda">Moneda</label>
                <input type="text" id="moneda" name="moneda" maxlength="3" value="{{ old('moneda', 'EUR') }}">
                @error('moneda')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="fuente_precio">Fuente precio</label>
                <input type="text" id="fuente_precio" name="fuente_precio" maxlength="50" value="{{ old('fuente_precio') }}" placeholder="manual, openprices...">
                @error('fuente_precio')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="url_origen">URL origen</label>
                <input type="url" id="url_origen" name="url_origen" maxlength="2048" value="{{ old('url_origen') }}">
                @error('url_origen')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="fecha_precio">Fecha precio</label>
                <input type="date" id="fecha_precio" name="fecha_precio" value="{{ old('fecha_precio') }}">
                @error('fecha_precio')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Guardar precio</button>
                <a href="{{ route('precios.index') }}" class="button">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
