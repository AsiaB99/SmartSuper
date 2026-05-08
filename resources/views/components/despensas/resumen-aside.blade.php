@props([
    'class' => '',
])

<aside {{ $attributes->merge(['class' => "h-fit rounded-[15px] border-t-[5px] border-accent-500 bg-white shadow-[0_10px_30px_rgba(0,0,0,0.08)] lg:sticky lg:top-24 {$class}"]) }}>
    {{ $slot }}
</aside>
