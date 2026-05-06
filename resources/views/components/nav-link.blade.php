@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 text-sm font-semibold text-brand-500 underline transition duration-300 hover:scale-105'
            : 'inline-flex items-center px-3 py-2 text-sm font-medium text-ink-700 transition duration-300 hover:scale-105 hover:text-brand-500';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
