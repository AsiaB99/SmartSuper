<x-guest-layout>
    <section class="mx-auto w-full max-w-[500px] rounded-[24px] border border-white/70 bg-white/95 p-8 shadow-[0_15px_35px_rgba(0,0,0,0.16)] backdrop-blur sm:p-10">
        <div class="ss-icon-bubble bg-brand-50">
            <x-ui.icon name="user-plus" class="h-12 w-12" />
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('auth.forgot_password_kicker') }}</p>
            <h1 class="mt-2 text-3xl font-semibold leading-tight text-ink-900 sm:text-4xl">{{ __('auth.forgot_password_heading') }}</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-ink-600">{{ __('auth.forgot_password_copy') }}</p>
        </div>

        <x-auth-session-status class="mb-5 mt-6 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <x-input-label for="email" :value="__('auth.email')" />
                <x-text-input id="email" class="ss-input mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
            </div>

            <button type="submit" class="ss-btn-green w-full">
                {{ __('auth.send_reset_link') }}
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-ink-600">
            <a class="font-semibold text-brand-700 underline transition hover:text-brand-800" href="{{ route('login') }}">
                {{ __('auth.back_to_login') }}
            </a>
        </div>
    </section>
</x-guest-layout>
