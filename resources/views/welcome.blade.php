<!DOCTYPE html>
<html lang="ru" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="VibeRoom — голосовые и текстовые каналы для твоего комьюнити. Создавай сервер, приглашай друзей и общайся без ограничений.">
    <title>{{ config('app.name', 'VibeRoom') }} — общайся голосом и текстом со своим комьюнити</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon-180.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-[#1E1F22] text-gray-100" x-data="{ mobileNav: false }">

    {{-- ============================== HEADER ============================== --}}
    <header class="fixed top-0 inset-x-0 z-50 border-b border-white/5 bg-[#1E1F22]/85 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5 shrink-0">
                <img src="{{ asset('images/logo-emblem.png') }}" alt="VibeRoom" class="w-8 h-8 rounded-lg object-contain">
                <span class="text-lg font-extrabold tracking-tight text-white">VibeRoom</span>
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-300">
                <a href="#features" class="hover:text-white transition-colors">Возможности</a>
                <a href="#communities" class="hover:text-white transition-colors">Комьюнити</a>
                <a href="#faq" class="hover:text-white transition-colors">Вопросы</a>
                <a href="https://github.com/Valter827/viberoom-app" target="_blank" rel="noopener" class="hover:text-white transition-colors">GitHub</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-semibold text-gray-200 hover:text-white hover:bg-white/5 transition-colors">
                    Войти
                </a>
                <a href="{{ route('register') }}" class="btn-lift px-4 py-2 rounded-lg text-sm font-semibold bg-[#5865F2] hover:bg-[#4752C4] text-white">
                    Регистрация
                </a>
            </div>

            <button @click="mobileNav = !mobileNav" class="md:hidden icon-action w-10 h-10 text-gray-200" aria-label="Меню">
                <svg x-show="!mobileNav" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="mobileNav" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div x-show="mobileNav" x-cloak x-transition class="md:hidden border-t border-white/5 bg-[#1E1F22] px-6 py-4 space-y-3">
            <a href="#features" class="block text-gray-300 hover:text-white" @click="mobileNav = false">Возможности</a>
            <a href="#communities" class="block text-gray-300 hover:text-white" @click="mobileNav = false">Комьюнити</a>
            <a href="#faq" class="block text-gray-300 hover:text-white" @click="mobileNav = false">Вопросы</a>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('login') }}" class="flex-1 text-center px-4 py-2 rounded-lg text-sm font-semibold border border-gray-700 text-gray-200">Войти</a>
                <a href="{{ route('register') }}" class="flex-1 text-center px-4 py-2 rounded-lg text-sm font-semibold bg-[#5865F2] text-white">Регистрация</a>
            </div>
        </div>
    </header>

    {{-- ============================== HERO ============================== --}}
    <section class="relative overflow-hidden pt-40 pb-24 px-6">
        {{-- фоновые градиентные пятна --}}
        <div class="pointer-events-none absolute -top-32 left-1/2 -translate-x-1/2 w-[900px] h-[900px] rounded-full bg-[#5865F2]/20 blur-[140px]"></div>
        <div class="pointer-events-none absolute top-40 right-0 w-[500px] h-[500px] rounded-full bg-purple-600/10 blur-[120px]"></div>

        <div class="relative max-w-5xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-gray-300 mb-6">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Открытый проект, бесплатно и без ограничений
            </div>

            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white leading-[1.1]">
                Комьюнити, которое<br class="hidden sm:block"> хочется <span class="bg-gradient-to-r from-[#7B84F7] to-[#B98DF2] bg-clip-text text-transparent">открывать каждый день</span>
            </h1>

            <p class="mt-6 text-lg text-gray-400 max-w-2xl mx-auto">
                VibeRoom — это голосовые и текстовые каналы, роли, права доступа и мгновенные уведомления
                в одном месте. Создай сервер за минуту и собери своих людей вместе.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a href="{{ route('register') }}"
                   class="btn-lift w-full sm:w-auto px-8 py-3.5 rounded-xl bg-[#5865F2] hover:bg-[#4752C4] text-white font-semibold text-center shadow-lg shadow-[#5865F2]/30">
                    Создать аккаунт бесплатно
                </a>
                <a href="{{ route('login') }}"
                   class="btn-lift w-full sm:w-auto px-8 py-3.5 rounded-xl bg-white/5 hover:bg-white/10 text-gray-100 font-semibold text-center border border-white/10">
                    Войти
                </a>
            </div>
            <p class="mt-4 text-xs text-gray-500">Не нужна банковская карта — регистрация занимает меньше минуты</p>
        </div>

        {{-- ------- Мокап интерфейса приложения ------- --}}
        <div class="relative max-w-6xl mx-auto mt-20">
            <div class="absolute -inset-x-6 -inset-y-6 bg-gradient-to-b from-[#5865F2]/10 to-transparent rounded-[2rem] blur-2xl"></div>
            <div class="relative rounded-2xl border border-white/10 bg-[#2B2D31] shadow-2xl overflow-hidden">
                {{-- title bar --}}
                <div class="flex items-center gap-1.5 px-4 h-9 bg-[#232428] border-b border-black/20">
                    <span class="w-3 h-3 rounded-full bg-[#ff5f57]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#febc2e]"></span>
                    <span class="w-3 h-3 rounded-full bg-[#28c840]"></span>
                </div>

                <div class="flex h-[420px]">
                    {{-- server rail --}}
                    <div class="hidden sm:flex w-[72px] shrink-0 bg-[#1E1F22] flex-col items-center py-3 gap-2">
                        <div class="w-11 h-11 rounded-2xl bg-[#5865F2] flex items-center justify-center text-white font-bold">V</div>
                        <div class="w-8 h-px bg-white/10 my-1"></div>
                        @foreach (['G','D','M','A'] as $letter)
                            <div class="w-11 h-11 rounded-2xl bg-[#313338] hover:bg-[#5865F2] transition-colors flex items-center justify-center text-gray-300 font-semibold text-sm">{{ $letter }}</div>
                        @endforeach
                    </div>

                    {{-- channel list --}}
                    <div class="hidden md:flex w-56 shrink-0 bg-[#2B2D31] flex-col">
                        <div class="h-12 flex items-center px-4 border-b border-black/20 font-semibold text-white text-sm">Мой сервер</div>
                        <div class="p-3 space-y-1 text-sm text-gray-400">
                            <div class="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Текст</div>
                            <div class="px-2 py-1.5 rounded bg-white/5 text-gray-100"># общий</div>
                            <div class="px-2 py-1.5 rounded hover:bg-white/5"># новости</div>
                            <div class="px-2 py-1.5 rounded hover:bg-white/5"># мемы</div>
                            <div class="px-2 py-1 mt-3 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Голос</div>
                            <div class="px-2 py-1.5 rounded hover:bg-white/5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4a1 1 0 012 0v12a1 1 0 11-2 0V4z"/></svg>
                                Общий войс
                            </div>
                            <div class="ml-4 flex items-center gap-2 text-xs text-gray-300">
                                <span class="w-5 h-5 rounded-full bg-emerald-500/80"></span> Алекс
                            </div>
                        </div>
                    </div>

                    {{-- chat area --}}
                    <div class="flex-1 flex flex-col bg-[#313338]">
                        <div class="h-12 flex items-center px-4 border-b border-black/20 text-sm text-gray-200 font-medium gap-2">
                            <span class="text-gray-500">#</span> общий
                        </div>
                        <div class="flex-1 p-4 space-y-4 overflow-hidden">
                            <div class="flex gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500 shrink-0"></div>
                                <div>
                                    <div class="text-sm"><span class="font-semibold text-white">Марина</span> <span class="text-[11px] text-gray-500 ml-1">сегодня в 14:02</span></div>
                                    <div class="text-sm text-gray-300 mt-0.5">Го сегодня в 8 вечера, погоняем немного 🎮</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 shrink-0"></div>
                                <div>
                                    <div class="text-sm"><span class="font-semibold text-white">Алекс</span> <span class="text-[11px] text-gray-500 ml-1">сегодня в 14:05</span></div>
                                    <div class="text-sm text-gray-300 mt-0.5">Я в деле, залетаю в войс 🔊</div>
                                </div>
                            </div>
                            <div class="flex gap-3 opacity-60">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 shrink-0"></div>
                                <div>
                                    <div class="text-sm"><span class="font-semibold text-white">Дима</span> <span class="text-[11px] text-gray-500 ml-1">печатает…</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="rounded-xl bg-[#383A40] px-4 py-2.5 text-sm text-gray-500">Написать в #общий…</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== LOGOS / TRUST ============================== --}}
    <section class="py-10 border-y border-white/5 bg-[#232428]">
        <div class="max-w-5xl mx-auto px-6 flex flex-wrap items-center justify-center gap-x-12 gap-y-4 text-sm text-gray-500">
            <span class="font-medium text-gray-400">Открытый исходный код</span>
            <span>•</span>
            <span>Laravel + Alpine.js</span>
            <span>•</span>
            <span>Голос в реальном времени</span>
            <span>•</span>
            <span>Бесплатно навсегда</span>
        </div>
    </section>

    {{-- ============================== FEATURES ============================== --}}
    <section id="features" class="py-24 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-semibold uppercase tracking-widest text-[#8992F5]">Возможности</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-white">Всё нужное для живого комьюнити</h2>
                <p class="mt-4 text-gray-400">От голосовых каналов до тонких прав доступа — VibeRoom даёт инструменты, которые обычно есть только у больших платформ.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $features = [
                        ['icon' => 'mic', 'title' => 'Голосовые каналы', 'text' => 'Чёткая голосовая связь без задержек, безлимитное количество участников в канале.'],
                        ['icon' => 'chat', 'title' => 'Текстовые каналы', 'text' => 'Структурируй общение по темам, закрепляй важное, реагируй эмодзи и упоминай участников.'],
                        ['icon' => 'shield', 'title' => 'Роли и права', 'text' => 'Гибкая система ролей: настраивай, кто может писать, модерировать или управлять сервером.'],
                        ['icon' => 'users', 'title' => 'Друзья и статусы', 'text' => 'Список друзей, личные сообщения и статус «в сети» — оставайся на связи вне серверов.'],
                        ['icon' => 'bell', 'title' => 'Уведомления и упоминания', 'text' => 'Мгновенные уведомления о упоминаниях и ответах, чтобы не пропустить важное.'],
                        ['icon' => 'settings', 'title' => 'Гибкая настройка сервера', 'text' => 'Создавай категории каналов, приглашения и настраивай сервер под своё комьюнити.'],
                    ];
                    $icons = [
                        'mic' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z"/>',
                        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>',
                        'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
                        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>',
                        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
                        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/>',
                    ];
                @endphp

                @foreach ($features as $f)
                    <div class="vr-card p-6 rounded-2xl bg-[#2B2D31] border border-white/5 hover:border-[#5865F2]/40 transition-colors">
                        <div class="w-11 h-11 rounded-xl bg-[#5865F2]/15 flex items-center justify-center text-[#8992F5] mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">{!! $icons[$f['icon']] !!}</svg>
                        </div>
                        <h3 class="text-white font-semibold mb-1.5">{{ $f['title'] }}</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $f['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== CTA COMMUNITIES ============================== --}}
    <section id="communities" class="py-20 px-6">
        <div class="max-w-5xl mx-auto rounded-3xl bg-gradient-to-br from-[#5865F2] to-[#7B4DF2] p-10 sm:p-16 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.15),transparent_60%)]"></div>
            <div class="relative">
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">Собери своё комьюнити уже сегодня</h2>
                <p class="mt-4 text-white/85 max-w-xl mx-auto">Создай сервер, настрой каналы под себя и пригласи друзей — это займёт пару минут.</p>
                <a href="{{ route('register') }}" class="btn-lift inline-block mt-8 px-8 py-3.5 rounded-xl bg-white text-[#4752C4] font-bold hover:bg-gray-100">
                    Начать бесплатно
                </a>
            </div>
        </div>
    </section>

    {{-- ============================== FAQ ============================== --}}
    <section id="faq" class="py-20 px-6">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-12">
                <span class="text-xs font-semibold uppercase tracking-widest text-[#8992F5]">Вопросы</span>
                <h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-white">Частые вопросы</h2>
            </div>

            <div class="space-y-3" x-data="{ open: 1 }">
                @php
                    $faq = [
                        ['q' => 'Сколько стоит VibeRoom?', 'a' => 'Регистрация и базовые функции полностью бесплатны — создавайте серверы, каналы и общайтесь без ограничений.'],
                        ['q' => 'Нужно ли устанавливать приложение?', 'a' => 'Нет, VibeRoom работает прямо в браузере. Ничего скачивать не нужно.'],
                        ['q' => 'Можно ли создать несколько серверов?', 'a' => 'Да, вы можете создавать и вступать в любое количество серверов под разные комьюнити и проекты.'],
                        ['q' => 'Проект с открытым исходным кодом?', 'a' => 'Да, исходный код доступен на GitHub — вы можете изучить его, предложить улучшения или развернуть свою копию.'],
                    ];
                @endphp
                @foreach ($faq as $i => $item)
                    <div class="vr-card rounded-2xl bg-[#2B2D31] border border-white/5 overflow-hidden">
                        <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left">
                            <span class="font-semibold text-white text-sm sm:text-base">{{ $item['q'] }}</span>
                            <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-transition x-cloak class="px-5 pb-4 text-sm text-gray-400 leading-relaxed">
                            {{ $item['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============================== FOOTER ============================== --}}
    <footer class="border-t border-white/5 bg-[#18191c] py-14 px-6">
        <div class="max-w-6xl mx-auto grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <img src="{{ asset('images/logo-emblem.png') }}" alt="VibeRoom" class="w-8 h-8 rounded-lg object-contain">
                    <span class="text-lg font-extrabold text-white">VibeRoom</span>
                </div>
                <p class="text-sm text-gray-500 max-w-xs">Голосовые и текстовые каналы для комьюнити, которые хотят быть рядом друг с другом.</p>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-3">Продукт</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="#features" class="hover:text-gray-300">Возможности</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-gray-300">Регистрация</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-gray-300">Вход</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-3">Проект</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="https://github.com/Valter827/viberoom-app" target="_blank" rel="noopener" class="hover:text-gray-300">GitHub</a></li>
                    <li><a href="#faq" class="hover:text-gray-300">Вопросы</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold text-sm mb-3">Связь</h4>
                <ul class="space-y-2 text-sm text-gray-500">
                    <li><a href="mailto:hello@valter.pp.ua" class="hover:text-gray-300">hello@valter.pp.ua</a></li>
                </ul>
            </div>
        </div>

        <div class="max-w-6xl mx-auto mt-12 pt-6 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600">
            <span>© {{ date('Y') }} VibeRoom. Все права защищены.</span>
            <span class="inline-flex items-center gap-1">Сделано с <x-icon name="heart" class="w-3.5 h-3.5 text-red-400" /> на Laravel</span>
        </div>
    </footer>

</body>
</html>
