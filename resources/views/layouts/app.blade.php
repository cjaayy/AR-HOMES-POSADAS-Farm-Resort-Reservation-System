<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AR Homes Posadas Farm Resort - Mariveles, Bataan')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/ar-homes-logo.png') }}" />
    <link rel="shortcut icon" href="{{ asset('images/logo/ar-homes-logo.png') }}" />

    <!-- Meta tags -->
    <meta name="theme-color" content="#667eea" />
    <meta name="description" content="AR Homes Posadas Farm Resort - Premium resort reservation system in Mariveles, Bataan. Experience luxury accommodations and world-class amenities." />
    <meta name="keywords" content="resort, accommodation, booking, AR Homes, Posadas Farm, luxury resort, Mariveles, Bataan, Philippines" />
    <meta name="author" content="AR Homes Posadas Farm Resort" />

    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" />

    <!-- Global Styles -->
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}" />
    @stack('styles')
</head>
<body>
    @yield('content')

    <!-- CSRF Setup for AJAX calls -->
    <script>
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    </script>
    @stack('scripts')
</body>
</html>
