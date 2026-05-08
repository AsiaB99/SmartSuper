<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'SmartSuper'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ss-page min-h-screen font-sans">
    @include('layouts.navigation')

    <main class="mx-auto flex w-full flex-col">
        @if (session('status'))
            <div class="ss-container pt-6">
                <div class="rounded-[10px] border border-brand-200 bg-brand-50 px-5 py-4 text-sm font-medium text-brand-800 shadow-soft">
                {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <x-layouts.footer />
</body>
</html>
