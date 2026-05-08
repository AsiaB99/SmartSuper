@extends('layouts.app')

@section('title', 'Finalizar lista | SmartSuper')

@section('content')
    <section class="ss-section bg-fondo-claro">
        <div class="ss-container">
            <section class="mx-auto max-w-3xl rounded-[20px] bg-white p-8 shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase text-brand-600">Finalizar compra</p>
                    <h1 class="text-3xl font-semibold text-ink-900">{{ $lista->nombre_lista }}</h1>
                    <p class="mt-2 text-sm text-ink-600">
                        ¿Deseas agregar los productos y cantidades de esta lista a una despensa editable?
                    </p>
                </div>

                <form class="grid gap-5" action="{{ route('listas.finalizar', $lista) }}" method="POST">
                    @csrf
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-ink-700">Despensa destino (opcional)</span>
                        <select class="ss-input" name="id_despensa">
                            <option value="">No agregar a despensa</option>
                            @foreach ($despensasEditables as $despensa)
                                <option value="{{ $despensa->id }}" @selected((int) old('id_despensa') === $despensa->id)>
                                    {{ $despensa->nombre_despensa }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_despensa')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    </label>

                    <div class="flex flex-wrap gap-3">
                        <button class="ss-btn-green" type="submit">Confirmar finalización</button>
                        <a class="ss-btn-outline" href="{{ route('listas.productos', $lista) }}">Volver</a>
                    </div>
                </form>
            </section>
        </div>
    </section>
@endsection
