<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2">
        <span class="text-sm font-semibold text-ink-700">Nombre de la despensa</span>
        <input class="ss-input" type="text" name="nombre_despensa" value="{{ old('nombre_despensa', $despensa->nombre_despensa ?? '') }}" maxlength="50" required>
        @error('nombre_despensa')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
    </label>

    <label class="grid gap-2">
        <span class="text-sm font-semibold text-ink-700">Fecha de creación</span>
        <input class="ss-input" type="datetime-local" name="fecha_creacion" value="{{ old('fecha_creacion', $despensa?->fecha_creacion?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
        @error('fecha_creacion')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
    </label>
</div>
