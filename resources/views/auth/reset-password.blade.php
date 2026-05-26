<x-guest-layout>
    <section class="mx-auto w-full max-w-[500px] rounded-[24px] border border-white/70 bg-white/95 p-8 shadow-[0_15px_35px_rgba(0,0,0,0.16)] backdrop-blur sm:p-10">
        <div class="ss-icon-bubble bg-brand-50">
            <x-ui.icon name="user-plus" class="h-12 w-12" />
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('auth.reset_password_kicker') }}</p>
            <h1 class="mt-2 text-3xl font-semibold leading-tight text-ink-900 sm:text-4xl">{{ __('auth.reset_password_heading') }}</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-ink-600">{{ __('auth.reset_password_copy') }}</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-input-label for="email" :value="__('auth.email')" />
                <x-text-input id="email" class="ss-input mt-2 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="password" :value="__('auth.password')" />
                <x-text-input id="password" class="ss-input mt-2 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-rose-600" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('auth.password_confirmation')" />
                <x-text-input id="password_confirmation" class="ss-input mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-sm text-rose-600" />
            </div>

            <button type="submit" class="ss-btn-green w-full">
                {{ __('auth.reset_password_submit') }}
            </button>
        </form>
    </section>
</x-guest-layout>
