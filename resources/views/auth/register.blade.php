<x-guest-layout>
    <section class="w-full max-w-[420px] rounded-[20px] bg-white/95 p-10 shadow-[0_15px_35px_rgba(0,0,0,0.20)] backdrop-blur">
        <div class="mb-5 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center text-brand-500">
                <x-ui.icon name="shopping-bag" class="h-12 w-12" />
            </div>
        </div>

        <div class="mb-8 grid grid-cols-2 rounded-[12px] border border-[var(--color-borde-suave)] bg-white p-1 text-center text-sm font-semibold">
            <a href="{{ route('login') }}" class="rounded-[10px] px-4 py-3 text-ink-500 transition hover:bg-brand-50">Iniciar sesión</a>
            <a href="{{ route('register') }}" class="rounded-[10px] bg-brand-500 px-4 py-3 text-white shadow-[0_4px_6px_rgba(46,204,113,0.20)]">Registrarse</a>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" value="Nombre" />
                <x-text-input id="name" class="ss-input mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="ss-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="password" value="Contraseña" />
                <x-text-input id="password" class="ss-input mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                <x-text-input id="password_confirmation" class="ss-input mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-rose-600" />
            </div>

            <button type="submit" class="ss-btn-green w-full">
                Registrarse
            </button>
        </form>
    </section>
</x-guest-layout>

