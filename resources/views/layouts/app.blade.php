<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'VibeRoom') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/echo.js'])
</head>
{{-- Discord-style тёмная палитра: фон #1E1F22 / #2B2D31 / #313338, акцент — фиолетовый #5865F2 --}}
<body class="h-full bg-[#1E1F22] text-gray-100 antialiased">
    {{ $slot ?? '' }}
    @yield('content')
</body>
</html>
