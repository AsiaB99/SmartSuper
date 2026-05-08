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
    <body class="min-h-screen bg-white font-sans text-ink-900 antialiased">
        @include('layouts.navigation')

        <main class="flex min-h-[calc(100vh-70px)] w-full items-center justify-center bg-[linear-gradient(rgba(0,0,0,0.60),rgba(0,0,0,0.60)),url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center px-4 py-10">
            {{ $slot }}
        </main>

        <x-layouts.footer />
    </body>
</html>

