<x-guest-layout>
    <section class="mx-auto w-full max-w-[420px] rounded-[20px] bg-white/95 p-10 shadow-[0_15px_35px_rgba(0,0,0,0.20)] backdrop-blur">
        <div class="mb-5 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center text-brand-500">
                <x-ui.icon name="shopping-bag" class="h-12 w-12" />
            </div>
        </div>

        <div class="mb-8 grid grid-cols-2 rounded-[12px] border border-[var(--color-borde-suave)] bg-white p-1 text-center text-sm font-semibold">
            <a href="{{ route('login') }}" class="rounded-[10px] px-4 py-3 text-ink-500 transition hover:bg-brand-50">{{ __('auth.login_tab') }}</a>
            <a href="{{ route('register') }}" class="rounded-[10px] bg-brand-500 px-4 py-3 text-white shadow-[0_4px_6px_rgba(46,204,113,0.20)]">{{ __('auth.register_tab') }}</a>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" class="ss-input mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="nombre_usuario" :value="__('auth.username')" />
                <x-text-input id="nombre_usuario" class="ss-input mt-2 block w-full" type="text" name="nombre_usuario" :value="old('nombre_usuario')" required autocomplete="nickname" />
                <x-input-error :messages="$errors->get('nombre_usuario')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="email" :value="__('auth.email')" />
                <x-text-input id="email" class="ss-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div x-data="{ show: false }">
                <x-input-label for="password" :value="__('auth.password')" />
                <div class="relative mt-2">
                    <x-text-input id="password" class="ss-input block w-full pr-14" x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="new-password" />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 inline-flex w-12 items-center justify-center text-ink-500 transition hover:text-ink-800"
                        x-on:click="show = !show"
                        x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <x-ui.icon name="eye" class="h-5 w-5" x-show="!show" />
                        <x-ui.icon name="eye-slash" class="h-5 w-5" x-show="show" x-cloak />
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('auth.password_confirmation')" />
                <x-text-input id="password_confirmation" class="ss-input mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-rose-600" />
            </div>

            <button type="submit" class="ss-btn-green w-full">
                {{ __('auth.submit_register') }}
            </button>
        </form>
    </section>
</x-guest-layout>
