@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-brand-100 bg-white/90 px-4 py-3 text-ink-900 shadow-sm focus:border-brand-400 focus:ring-brand-300']) }}>
