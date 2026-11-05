<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ARO Visayas PME')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    @stack('styles')
    <style>
        :root {
            --color-primary: #d97706;
            --color-primary-light: #f59e0b;
            --color-accent: #fbbf24;
            --color-accent-hover: #f59e0b;
            --color-surface: #f8fafc;
            --color-header: #92400e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Poppins', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--color-surface);
            color: #333;
            line-height: 1.6;
        }

        .app-header {
            background: var(--color-header);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .app-header h1 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .app-header__user {
            font-size: 0.9rem;
            opacity: 0.95;
        }

        .app-main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--color-accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--color-accent-hover);
        }

        .status-go { background: #27ae60; color: #fff; }
        .status-no-go { background: #e74c3c; color: #fff; }
        .status-compliance { background: #f1c40f; color: #333; }
        .status-waiting { background: #3498db; color: #fff; }
        .status-pending { background: #95a5a6; color: #fff; }
    </style>
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
