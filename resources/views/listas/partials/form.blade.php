<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 md:col-span-2">
        <span class="text-sm font-semibold text-ink-700">Nombre de la lista</span>
        <input class="ss-input" type="text" name="nombre_lista" value="{{ old('nombre_lista', $lista->nombre_lista ?? '') }}" maxlength="50" required>
        @error('nombre_lista')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
    </label>

    <label class="grid gap-2">
        <span class="text-sm font-semibold text-ink-700">Estado</span>
        <select class="ss-input" name="estado" required>
            <option value="activa" @selected(old('estado', $lista->estado ?? 'activa') === 'activa')>Activa</option>
            <option value="comprada" @selected(old('estado', $lista->estado ?? '') === 'comprada')>Comprada</option>
        </select>
        @error('estado')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
    </label>

    <label class="grid gap-2">
        <span class="text-sm font-semibold text-ink-700">Fecha de creación</span>
        <input class="ss-input" type="datetime-local" name="fecha_creacion" value="{{ old('fecha_creacion', $lista?->fecha_creacion?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
        @error('fecha_creacion')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
    </label>
</div>
