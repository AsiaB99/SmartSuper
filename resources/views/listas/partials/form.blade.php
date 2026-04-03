<div class="field-grid">
    <label class="field">
        <span>Nombre de la lista</span>
        <input type="text" name="nombre_lista" value="{{ old('nombre_lista', $lista->nombre_lista ?? '') }}" maxlength="50" required>
        @error('nombre_lista')<small>{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Estado</span>
        <select name="estado" required>
            <option value="activa" @selected(old('estado', $lista->estado ?? 'activa') === 'activa')>Activa</option>
            <option value="comprada" @selected(old('estado', $lista->estado ?? '') === 'comprada')>Comprada</option>
        </select>
        @error('estado')<small>{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Fecha de creación</span>
        <input type="datetime-local" name="fecha_creacion" value="{{ old('fecha_creacion', $lista?->fecha_creacion?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
        @error('fecha_creacion')<small>{{ $message }}</small>@enderror
    </label>
</div>
