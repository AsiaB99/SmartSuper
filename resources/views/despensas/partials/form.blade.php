<div class="field-grid">
    <label class="field">
        <span>Nombre de la despensa</span>
        <input type="text" name="nombre_despensa" value="{{ old('nombre_despensa', $despensa->nombre_despensa ?? '') }}" maxlength="50" required>
        @error('nombre_despensa')<small>{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Fecha de creación</span>
        <input type="datetime-local" name="fecha_creacion" value="{{ old('fecha_creacion', $despensa?->fecha_creacion?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
        @error('fecha_creacion')<small>{{ $message }}</small>@enderror
    </label>
</div>
