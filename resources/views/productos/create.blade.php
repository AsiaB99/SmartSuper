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

            <div class="form-actions">
                <button type="submit" class="button button--primary">Crear producto</button>
                <a href="{{ route('productos.index') }}" class="button">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
