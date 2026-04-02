<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'SmartSuper'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    <main class="app-frame">
        @if (session('status'))
            <div class="alert-card alert-card--success">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
