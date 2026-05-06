@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg bg-brand-600 px-4 py-3 text-start text-base font-semibold text-white shadow-soft transition'
            : 'block w-full rounded-lg px-4 py-3 text-start text-base font-medium text-ink-700 transition hover:bg-brand-50 hover:text-brand-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
