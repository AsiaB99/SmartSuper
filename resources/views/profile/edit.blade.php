<x-app-layout>
    @php
        $abrirInformacion = ! $errors->any() || $errors->hasAny(['name', 'nombre_usuario', 'email']);
        $abrirContrasena = $errors->updatePassword->isNotEmpty();
        $abrirEliminar = $errors->userDeletion->isNotEmpty();
    @endphp

    <div class="space-y-8">
            <section class="relative mb-8 min-h-[150px] overflow-hidden rounded-[20px] p-8 text-center shadow-[0_10px_30px_rgba(0,0,0,0.05)]">
                <img
                    src="{{ asset('img/encabezados/encabezado_lista.png') }}"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                    aria-hidden="true"
                >
                <div class="absolute inset-0 bg-white/55" aria-hidden="true"></div>
                <h1 class="relative text-4xl font-semibold text-ink-900">Perfil</h1>
                <p class="relative mx-auto mt-3 max-w-3xl text-lg leading-7 text-ink-600">
                    Gestiona tus datos personales, credenciales y seguridad de cuenta.
                </p>
            </section>

            <div class="grid gap-5">
                <details class="group overflow-hidden rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]" @if($abrirInformacion) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-5 text-left">
                        <div>
                            <h2 class="text-xl font-semibold text-ink-900">Información del perfil</h2>
                            <p class="mt-1 text-sm text-ink-600">Nombre, usuario y correo electrónico.</p>
                        </div>
                        <span class="text-2xl leading-none text-brand-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <div class="border-t border-[var(--color-borde-suave)] px-6 py-6">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-profile-information-form')
                        </div>
                    </div>
                </details>

                <details class="group overflow-hidden rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]" @if($abrirContrasena) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-5 text-left">
                        <div>
                            <h2 class="text-xl font-semibold text-ink-900">Actualizar contraseña</h2>
                            <p class="mt-1 text-sm text-ink-600">Refuerza el acceso con una clave segura.</p>
                        </div>
                        <span class="text-2xl leading-none text-brand-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <div class="border-t border-[var(--color-borde-suave)] px-6 py-6">
                        <div class="max-w-2xl">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </details>

                <details class="group overflow-hidden rounded-[15px] bg-white shadow-[0_4px_10px_rgba(0,0,0,0.03)]" @if($abrirEliminar) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-5 text-left">
                        <div>
                            <h2 class="text-xl font-semibold text-ink-900">Eliminar cuenta</h2>
                            <p class="mt-1 text-sm text-ink-600">Acción irreversible sobre tus datos.</p>
                        </div>
                        <span class="text-2xl leading-none text-rose-600 transition group-open:rotate-45">+</span>
                    </summary>
                    <div class="border-t border-[var(--color-borde-suave)] px-6 py-6">
                        <div class="max-w-2xl">
                            @include('profile.partials.delete-user-form')
                        </div>
                    </div>
                </details>
            </div>
    </div>
</x-app-layout>
