<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-full border border-brand-200 bg-white px-5 py-3 text-sm font-semibold text-brand-800 shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
