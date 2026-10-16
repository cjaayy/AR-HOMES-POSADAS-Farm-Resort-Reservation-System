<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Portal - AR Homes Posadas Farm Resort')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
    @stack('styles')
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        @include('partials.sidebar')
        <main class="admin-main">
            @yield('content')
        </main>
    </div>

    <script>
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    </script>
    @stack('scripts')
</body>
</html>
