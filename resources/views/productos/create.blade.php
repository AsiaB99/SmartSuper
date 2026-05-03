@extends('layouts.app')

@section('title', 'Nuevo producto | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Crear producto</h1>
            <p class="hero-copy">Agrega un nuevo producto al catálogo disponible.</p>
        </div>
    </section>

    <section class="panel-card">
        <form action="{{ route('admin.productos.store') }}" method="POST">
            @csrf

            <fieldset>
                <label for="nombre_producto">Nombre del producto</label>
                <input type="text" id="nombre_producto" name="nombre_producto" value="{{ old('nombre_producto') }}" required>
                @error('nombre_producto')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="codigo_barras">Código de barras</label>
                <input type="text" id="codigo_barras" name="codigo_barras" value="{{ old('codigo_barras') }}" maxlength="32">
                @error('codigo_barras')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="id_seccion">Sección</label>
                <select id="id_seccion" name="id_seccion" required>
                    <option value="">-- Selecciona una sección --</option>
                    @foreach ($secciones as $seccion)
                        <option value="{{ $seccion->id }}" {{ old('id_seccion') == $seccion->id ? 'selected' : '' }}>
                            {{ $seccion->nombre_seccion }}
                        </option>
                    @endforeach
                </select>
                @error('id_seccion')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="marca">Marca</label>
                <input type="text" id="marca" name="marca" value="{{ old('marca') }}" maxlength="50">
                @error('marca')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="formato">Formato</label>
                <input type="text" id="formato" name="formato" value="{{ old('formato') }}" maxlength="50">
                @error('formato')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="cantidad_envase">Cantidad envase</label>
                <input type="number" id="cantidad_envase" name="cantidad_envase" value="{{ old('cantidad_envase') }}" min="0" step="0.001">
                @error('cantidad_envase')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="unidad_envase">Unidad envase</label>
                <input type="text" id="unidad_envase" name="unidad_envase" value="{{ old('unidad_envase') }}" maxlength="20" placeholder="kg, l, unidad">
                @error('unidad_envase')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="imagen">URL imagen</label>
                <input type="text" id="imagen" name="imagen" value="{{ old('imagen') }}" maxlength="255">
                @error('imagen')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="fuente_datos">Fuente de datos</label>
                <input type="text" id="fuente_datos" name="fuente_datos" value="{{ old('fuente_datos') }}" maxlength="50" placeholder="openfoodfacts, manual...">
                @error('fuente_datos')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Crear producto</button>
                <a href="{{ route('productos.index') }}" class="button">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
