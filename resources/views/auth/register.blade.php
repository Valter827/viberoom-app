<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'VibeRoom') }} — Регистрация</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen relative overflow-hidden flex items-center justify-center py-10 px-4"
         style="background: radial-gradient(circle at 15% 20%, rgba(255,255,255,0.06), transparent 40%), linear-gradient(135deg, #4c3fd6 0%, #3b3fc9 35%, #2f4bd6 65%, #3a6ff0 100%);">

        {{-- декоративные звёздочки --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <span class="absolute text-white/70" style="top:8%; left:6%; font-size:22px;">✦</span>
            <span class="absolute text-white/40" style="top:14%; left:12%; font-size:12px;">✦</span>
            <span class="absolute text-white/50" style="top:6%; right:14%; font-size:16px;">✦</span>
            <span class="absolute text-white/30" style="top:22%; right:8%; font-size:10px;">✦</span>
            <span class="absolute text-white/40" style="bottom:16%; left:8%; font-size:20px;">✦</span>
            <span class="absolute text-white/30" style="bottom:8%; left:20%; font-size:12px;">✦</span>
            <span class="absolute text-white/50" style="bottom:20%; right:12%; font-size:18px;">✦</span>
            <span class="absolute text-white/30" style="bottom:10%; right:22%; font-size:10px;">✦</span>
            <span class="absolute text-white/25" style="top:45%; left:4%; font-size:14px;">✦</span>
            <span class="absolute text-white/25" style="top:55%; right:5%; font-size:14px;">✦</span>
        </div>

        <div class="relative w-full max-w-md">
            <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 mb-5 text-white/70 hover:text-white transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                На главную
            </a>

        <div class="rounded-2xl p-8 shadow-2xl" style="background:#3a3d47;">

            <div class="flex justify-center mb-5">
                <img src="{{ asset('images/logo-emblem.png') }}" alt="VibeRoom" class="w-14 h-14 rounded-full object-contain">
            </div>

            <h1 class="text-white text-2xl font-bold text-center mb-6">Создать учётную запись</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-500/10 border border-red-500/40 px-4 py-3">
                    <ul class="text-sm text-red-300 list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        E-mail <span class="text-red-400">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Отображаемое имя --}}
                <div>
                    <label for="display_name" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Отображаемое имя
                    </label>
                    <input id="display_name" type="text" name="display_name" value="{{ old('display_name') }}" autocomplete="name"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Имя пользователя --}}
                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Имя пользователя <span class="text-red-400">*</span>
                    </label>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required autocomplete="nickname"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Пароль --}}
                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Пароль <span class="text-red-400">*</span>
                    </label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Подтверждение пароля --}}
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Подтверждение пароля <span class="text-red-400">*</span>
                    </label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-3 py-2.5 outline-none">
                </div>

                {{-- Дата рождения --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-300 mb-1.5">
                        Дата рождения <span class="text-red-400">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <select name="birth_day" required
                                class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-2 py-2.5 outline-none">
                            <option value="" disabled {{ old('birth_day') ? '' : 'selected' }}>День</option>
                            @for ($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" @selected(old('birth_day') == $d)>{{ $d }}</option>
                            @endfor
                        </select>

                        @php
                            $months = [
                                1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
                                5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
                                9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
                            ];
                        @endphp
                        <select name="birth_month" required
                                class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-2 py-2.5 outline-none">
                            <option value="" disabled {{ old('birth_month') ? '' : 'selected' }}>Месяц</option>
                            @foreach ($months as $num => $label)
                                <option value="{{ $num }}" @selected(old('birth_month') == $num)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="birth_year" required
                                class="w-full rounded-md bg-[#1e1f22] border border-[#1e1f22] focus:border-[#5865F2] focus:ring-0 text-gray-100 text-sm px-2 py-2.5 outline-none">
                            <option value="" disabled {{ old('birth_year') ? '' : 'selected' }}>Год</option>
                            @for ($y = now()->year - 13; $y >= 1930; $y--)
                                <option value="{{ $y }}" @selected(old('birth_year') == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Согласие на рассылку --}}
                <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                    <input type="checkbox" name="marketing_opt_in" value="1" {{ old('marketing_opt_in') ? 'checked' : '' }}
                           class="mt-0.5 rounded bg-[#1e1f22] border-gray-500 text-[#5865F2] focus:ring-0 focus:ring-offset-0">
                    <span class="text-xs text-gray-300 leading-relaxed">
                        (Необязательно) Я не против получать электронные письма с новостями {{ config('app.name', 'VibeRoom') }}, советами и специальными предложениями. От рассылки можно отписаться в любое время.
                    </span>
                </label>

                <p class="text-xs text-gray-400 leading-relaxed">
                    Нажимая кнопку «Создать учётную запись», вы соглашаетесь с
                    <a href="#" class="text-[#8ea1e1] hover:underline">Условиями использования</a> {{ config('app.name', 'VibeRoom') }} и подтверждаете, что ознакомились с
                    <a href="#" class="text-[#8ea1e1] hover:underline">Политикой конфиденциальности</a>.
                </p>

                <button type="submit"
                        class="btn-lift w-full rounded-md bg-[#a6b2fb] hover:bg-[#95a3f9] text-[#20232b] font-semibold text-sm py-2.5">
                    Создать учётную запись
                </button>

                <p class="text-xs text-gray-400 pt-1">
                    Уже зарегистрированы?
                    <a href="{{ route('login') }}" class="text-[#8ea1e1] hover:underline">Войти</a>
                </p>
            </form>
        </div>
        </div>
    </div>
</body>
</html>
