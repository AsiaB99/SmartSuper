<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SmartSuper') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(36,157,107,0.18),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(232,142,16,0.12),_transparent_28%),linear-gradient(180deg,_#f7f3ec_0%,_#edf4f2_100%)] text-ink-900">
            @include('layouts.navigation')

            @isset($header)
                <header class="mx-auto mt-6 w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="rounded-lg border border-white/70 bg-white/85 px-6 py-6 shadow-soft backdrop-blur">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
