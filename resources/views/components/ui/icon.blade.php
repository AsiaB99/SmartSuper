@props(['name'])

@php
    $map = [
        'archive-box' => 'archive',
        'arrow-right-end-on-rectangle' => 'log-out',
        'bars-3' => 'menu',
        'clipboard-document-list' => 'clipboard-list',
        'eye' => 'eye',
        'eye-slash' => 'eye-off',
        'list-bullet' => 'list',
        'magnifying-glass' => 'search',
        'map-pin' => 'map-pin',
        'pencil-square' => 'square-pen',
        'plus' => 'plus',
        'shopping-bag' => 'shopping-bag',
        'shopping-cart' => 'shopping-cart',
        'trash' => 'trash-2',
        'user-plus' => 'user-plus',
        'x-mark' => 'x',
    ];

    $icon = $map[$name] ?? 'circle';
    $defaultSizeClass = $attributes->has('class') ? '' : 'h-5 w-5';
@endphp

<x-dynamic-component :component="'lucide-' . $icon" {{ $attributes->merge(['class' => $defaultSizeClass]) }} />
