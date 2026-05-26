<x-guest-layout>
    <section class="mx-auto w-full max-w-[520px] rounded-[24px] border border-white/70 bg-white/95 p-8 shadow-[0_15px_35px_rgba(0,0,0,0.16)] backdrop-blur sm:p-10">
        <div class="ss-icon-bubble bg-brand-50">
            <x-ui.icon name="shopping-cart" class="h-12 w-12" />
        </div>

        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">{{ __('auth.verify_email_kicker') }}</p>
            <h1 class="mt-2 text-3xl font-semibold leading-tight text-ink-900 sm:text-4xl">{{ __('auth.verify_email_heading') }}</h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-7 text-ink-600">{{ __('auth.verify_email_copy') }}</p>
        </div>

        <div class="mt-6 rounded-[18px] border border-brand-100 bg-[linear-gradient(135deg,#f5fbf7_0%,#edf8f1_100%)] px-5 py-4 text-sm text-ink-700 shadow-[0_10px_24px_rgba(46,204,113,0.08)]">
            <p class="font-semibold text-brand-700">{{ __('auth.verify_email_tip_title') }}</p>
            <p class="mt-1 leading-6">{{ __('auth.verify_email_tip_copy') }}</p>
        </div>

        @if (session('status') === 'verification-link-sent')
            <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ __('auth.verify_email_resent') }}
            </div>
        @endif

        <div class="mt-6 space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <button type="submit" class="ss-btn-green w-full">
                    {{ __('auth.verify_email_resend') }}
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="ss-btn-outline w-full">
                    {{ __('auth.verify_email_logout') }}
                </button>
            </form>
        </div>
    </section>
</x-guest-layout>
