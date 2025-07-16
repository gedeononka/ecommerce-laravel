<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Admin Panel</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="font-sans antialiased bg-gray-200">
    <div class="flex min-h-screen">
        @include('components.sidebar')
        <div class="flex-1 p-6">
            @include('components.navbar')
            <main class="mt-6">
                @yield('content')
            </main>
        </div>
    </div>
    <script src="{{ asset('js/admin.js') }}"></script>
</body>
</html>