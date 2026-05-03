@extends('layouts.app')

@section('title', 'Editar supermercado | Admin')

@section('content')
    <section class="hero-card">
        <div>
            <p class="eyebrow">Admin</p>
            <h1>Editar supermercado</h1>
            <p class="hero-copy">Actualiza los datos del supermercado seleccionado.</p>
        </div>
    </section>

    <section class="panel-card">
        <form action="{{ route('admin.supermercados.update', $supermercado) }}" method="POST">
            @csrf
            @method('PUT')

            <fieldset>
                <label for="nombre_super">Nombre del supermercado</label>
                <input type="text" id="nombre_super" name="nombre_super" value="{{ old('nombre_super', $supermercado->nombre_super) }}" required>
                @error('nombre_super')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="latitud">Latitud</label>
                <input type="number" id="latitud" name="latitud" step="0.000001" value="{{ old('latitud', $supermercado->latitud) }}">
                @error('latitud')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset>
                <label for="longitud">Longitud</label>
                <input type="number" id="longitud" name="longitud" step="0.000001" value="{{ old('longitud', $supermercado->longitud) }}">
                @error('longitud')
                    <span class="error">{{ $message }}</span>
                @enderror
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="button button--primary">Actualizar supermercado</button>
                <a href="{{ route('supermercados.index') }}" class="button">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
