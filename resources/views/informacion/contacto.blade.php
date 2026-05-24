@extends('layouts.app')

@section('title', 'Contacto | SmartSuper')

@section('content')
    <section class="ss-section">
        <div class="ss-container max-w-3xl rounded-2xl bg-white p-8 shadow-soft">
            <header class="mb-8 space-y-3">
                <h1 class="text-3xl font-semibold text-ink-900">Contacto</h1>
                <p class="text-sm leading-6 text-ink-600">
                    Si tienes dudas, sugerencias o necesitas ayuda, escribenos y te responderemos en un plazo aproximado de 24-48 horas laborables.
                </p>
            </header>

            <form action="{{ route('contacto.enviar') }}" method="POST" class="space-y-6" novalidate>
                @csrf

                @error('contacto')
                    <div class="rounded-[10px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ $message }}
                    </div>
                @enderror

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="flex flex-col gap-2 text-sm font-medium text-ink-800">
                        <span>Nombre</span>
                        <input
                            type="text"
                            name="nombre"
                            value="{{ old('nombre') }}"
                            maxlength="120"
                            autocomplete="name"
                            placeholder="Tu nombre"
                            required
                            class="ss-input w-full"
                        >
                        @error('nombre')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    </label>

                    <label class="flex flex-col gap-2 text-sm font-medium text-ink-800">
                        <span>Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            maxlength="255"
                            autocomplete="email"
                            placeholder="tu@email.com"
                            required
                            class="ss-input w-full"
                        >
                        @error('email')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    </label>
                </div>

                <div class="grid gap-5">
                    <label class="flex flex-col gap-2 text-sm font-medium text-ink-800">
                        <span>Asunto (opcional)</span>
                        <input
                            type="text"
                            name="asunto"
                            value="{{ old('asunto') }}"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="Ej. Sugerencia para mejorar la app"
                            class="ss-input w-full"
                        >
                        @error('asunto')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    </label>

                    <label class="flex flex-col gap-2 text-sm font-medium text-ink-800">
                        <span>Mensaje</span>
                        <textarea
                            name="mensaje"
                            rows="6"
                            maxlength="2000"
                            placeholder="Cuentanos en detalle tu consulta..."
                            required
                            class="ss-input w-full"
                        >{{ old('mensaje') }}</textarea>
                        @error('mensaje')<small class="text-sm font-medium text-rose-600">{{ $message }}</small>@enderror
                    </label>
                </div>

                <input
                    type="text"
                    name="empresa"
                    tabindex="-1"
                    autocomplete="off"
                    class="hidden"
                    value=""
                >

                <p class="text-xs text-ink-500">
                    Al enviar este formulario aceptas que tratemos tus datos para responder a tu consulta.
                </p>

                <div>
                    <button type="submit" class="ss-btn-primary">
                        Enviar mensaje
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
