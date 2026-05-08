<x-guest-layout>
    <section class="w-full max-w-[420px] rounded-[20px] bg-white/95 p-10 shadow-[0_15px_35px_rgba(0,0,0,0.20)] backdrop-blur">
        <div class="mb-5 flex justify-center">
            <div class="flex h-16 w-16 items-center justify-center text-brand-500">
                <x-ui.icon name="shopping-bag" class="h-12 w-12" />
            </div>
        </div>

        <div class="mb-8 grid grid-cols-2 rounded-[12px] border border-[var(--color-borde-suave)] bg-white p-1 text-center text-sm font-semibold">
            <a href="{{ route('login') }}" class="rounded-[10px] bg-brand-500 px-4 py-3 text-white shadow-[0_4px_6px_rgba(46,204,113,0.20)]">{{ __('auth.login_tab') }}</a>
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="rounded-[10px] px-4 py-3 text-ink-500 transition hover:bg-brand-50">{{ __('auth.register_tab') }}</a>
            @endif
        </div>

        <div>
            <x-auth-session-status class="mb-5 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('auth.email')" />
                    <x-text-input id="email" class="ss-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('auth.password')" />
                    <x-text-input id="password" class="ss-input mt-2 block w-full" type="password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
                </div>

                <button type="submit" class="ss-btn-green w-full">
                    {{ __('auth.submit_login') }}
                </button>

                @if (Route::has('password.request'))
                    <div class="text-center">
                        <a class="text-sm font-medium text-ink-700 underline transition hover:text-brand-700" href="{{ route('password.request') }}">
                            {{ __('auth.forgot_password') }}
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </section>
</x-guest-layout>

