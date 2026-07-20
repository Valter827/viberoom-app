<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="current-user-id" content="{{ Auth::id() }}">
    @endauth
    <title>{{ config('app.name', 'VibeRoom') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Discord-style тёмная палитра: фон #1E1F22 / #2B2D31 / #313338, акцент — фиолетовый #5865F2 --}}
<body class="h-full bg-[#1E1F22] text-gray-100 antialiased">
    {{ $slot ?? '' }}
    @yield('content')
</body>
</html>
