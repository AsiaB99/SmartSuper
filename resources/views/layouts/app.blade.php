<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'SmartSuper'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
@php($esInicioPrincipal = request()->routeIs('dashboard'))

<body
    class="ss-page flex min-h-screen flex-col font-sans"
    data-search-suggestions-label="{{ __('js.search_suggestions') }}"
>
    @include('layouts.navigation')

    <main class="relative mx-auto flex w-full flex-1 flex-col {{ $esInicioPrincipal ? 'bg-transparent' : 'ss-page-gradient' }}">

        @if (session('status'))
            <div class="relative ss-container pt-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 2800)">
                <div
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-500"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="rounded-[10px] border border-brand-200 bg-brand-50 px-5 py-4 text-sm font-medium text-brand-800 shadow-soft"
                >
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <div class="relative">
            @yield('content')
        </div>
    </main>

    <x-layouts.footer />
    @stack('scripts')
</body>
</html>
