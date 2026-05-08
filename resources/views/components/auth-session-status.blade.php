@props(['status'])

@if ($status)
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 2800)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-500"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}
    >
        {{ $status }}
    </div>
@endif
