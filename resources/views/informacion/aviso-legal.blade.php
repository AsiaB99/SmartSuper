@extends('layouts.app')

@section('title', 'Aviso legal | SmartSuper')

@section('content')
    <section class="ss-section">
        <div class="ss-container max-w-4xl space-y-8 rounded-2xl bg-white p-8 shadow-soft">
            <header class="space-y-3">
                <h1 class="text-3xl font-semibold text-ink-900">Aviso legal</h1>
                <p class="text-sm text-ink-600">Informacion general sobre el uso de SmartSuper.</p>
            </header>

            <article class="space-y-5 text-sm leading-7 text-ink-700">
                <p>
                    Este sitio web es titularidad de SmartSuper y su uso implica la aceptacion de las presentes condiciones.
                    El contenido se ofrece con caracter informativo y puede actualizarse para mejorar el servicio.
                </p>
                <p>
                    El usuario se compromete a utilizar la plataforma de forma licita, sin afectar derechos de terceros ni
                    la disponibilidad del servicio.
                </p>
                <p>
                    SmartSuper no garantiza la ausencia total de interrupciones, aunque se aplican medidas razonables para
                    mantener la continuidad y seguridad de la aplicacion.
                </p>
            </article>
        </div>
    </section>
@endsection
