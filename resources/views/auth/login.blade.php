<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'VibeRoom') }} — Вход</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center py-10 px-4 bg-[#1e1f22]">

        <div class="w-full max-w-md rounded-2xl p-8 shadow-2xl bg-[#2b2d31]">

            <h1 class="text-white text-2xl font-bold text-center mb-2">С возвращением!</h1>
            <p class="text-gray-400 text-sm text-center mb-6">Мы так рады видеть вас снова!</p>

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/40 px-4 py-3">
                    <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-500/10 border border-red-500/40 px-4 py-3">
                    <ul class="text-sm text-red-300 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Email или номер телефона --}}
                <div>
                    <label for="login" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Адрес электронной почты или номер телефона <span class="text-red-400">*</span>
                    </label>
                    <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Пароль --}}
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Пароль <span class="text-red-400">*</span>
                    </label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="inline-block text-sm text-[#8ea1e1] hover:underline">
                        Забыли пароль?
                    </a>
                @endif

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded bg-[#1e1f22] border-gray-500 text-[#5865F2] focus:ring-0 focus:ring-offset-0">
                    <span class="text-sm text-gray-300">Запомнить меня</span>
                </label>

                <button type="submit"
                        class="w-full rounded-md bg-[#5865F2] hover:bg-[#4752c4] text-white font-semibold text-sm py-2.5 transition-colors">
                    Вход
                </button>

                <p class="text-sm text-gray-400 pt-1 text-center">
                    Нужна учётная запись?
                    <a href="{{ route('register') }}" class="text-[#8ea1e1] hover:underline">Зарегистрироваться</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
