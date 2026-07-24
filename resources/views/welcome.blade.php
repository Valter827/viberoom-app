<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VibeRoom') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">
    {{-- Discord-style тёмная палитра: фон #1E1F22 / #2B2D31 / #313338, акцент — фиолетовый #5865F2 --}}
    <div class="min-h-screen flex flex-col items-center justify-center bg-[#1E1F22] text-gray-100 px-6">

        <img src="{{ asset('images/logo-emblem.png') }}" alt="VibeRoom" class="w-32 h-32 rounded-full object-contain mb-6 shadow-2xl">

        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2">VibeRoom</h1>
        <p class="text-gray-400 mb-10 text-center max-w-sm">Общайся голосом и текстом со своими комьюнити</p>

        <div class="flex flex-col sm:flex-row gap-4 w-full max-w-xs sm:max-w-none justify-center">
            <a href="{{ route('login') }}"
               class="btn-lift px-8 py-3 rounded-lg bg-[#5865F2] hover:bg-[#4752C4] text-white font-semibold text-center">
                Войти
            </a>
            <a href="{{ route('register') }}"
               class="btn-lift px-8 py-3 rounded-lg bg-[#2B2D31] hover:bg-[#313338] text-gray-100 font-semibold text-center border border-gray-700">
                Регистрация
            </a>
        </div>
    </div>
</body>
</html>
