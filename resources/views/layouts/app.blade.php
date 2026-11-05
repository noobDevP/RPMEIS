<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ARO Visayas PME')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}">
    @stack('styles')
</head>
<body>
    @hasSection('header')
        @yield('header')
    @else
        <header class="app-header">
            <h1>@yield('header-title', 'ARO Visayas PME')</h1>
            <div class="app-header__user">@yield('header-user')</div>
        </header>
    @endif

    <main class="app-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
