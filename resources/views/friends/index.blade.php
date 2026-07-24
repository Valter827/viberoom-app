@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden">
    @include('servers.partials.server-sidebar')

    {{-- Вторая колонка: заголовок "Друзья" + личный профиль внизу, как у сервера --}}
    <aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
            <h1 class="font-semibold truncate">Личные сообщения</h1>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-3">
            <div class="px-2 py-2 rounded bg-[#404249] text-sm font-medium flex items-center gap-2">
                <span>🧑‍🤝‍🧑</span> Друзья
            </div>

            @if (Auth::user()->servers->isEmpty())
                <div class="mt-4 px-2">
                    <p class="text-xs text-gray-400 mb-2">У вас пока нет серверов.</p>
                    <a href="{{ route('servers.create') }}" class="block text-center text-xs bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 mb-2">
                        Создать сервер
                    </a>
                    <button x-data @click="$dispatch('open-join-modal')"
                            class="w-full text-center text-xs bg-[#3a3c42] hover:bg-[#43454b] rounded py-2">
                        Войти по коду приглашения
                    </button>
                </div>
            @endif
        </div>

        <div class="h-14 flex items-center px-2 bg-[#232428] flex-shrink-0">
            <div class="relative">
                <img src="{{ Auth::user()->avatar_url }}" class="w-8 h-8 rounded-full" alt="avatar">
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-[#232428]
                    {{ Auth::user()->status === 'online' ? 'bg-green-500' : (Auth::user()->status === 'idle' ? 'bg-yellow-500' : (Auth::user()->status === 'dnd' ? 'bg-red-500' : 'bg-gray-500')) }}">
                </span>
            </div>
            <div class="ml-2 leading-tight overflow-hidden">
                <p class="text-sm font-medium truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400 truncate">{{ ucfirst(Auth::user()->status) }}</p>
            </div>
            <button @click="$dispatch('open-profile-settings')" class="icon-action icon-gear ml-auto text-sm" title="Настройки профиля">⚙️</button>
        </div>
    </aside>

    {{-- Основная область: друзья + заявки + форма добавления --}}
    <main class="flex-1 flex flex-col bg-[#313338] overflow-y-auto">
        <div class="h-12 flex items-center px-4 border-b border-black/20 flex-shrink-0 justify-between">
            <span class="font-semibold">🧑‍🤝‍🧑 Друзья</span>
            @include('components.mentions-bell')
        </div>

        <div class="p-6 max-w-2xl mx-auto w-full">

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/40 px-4 py-3">
                    <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Форма добавления в друзья --}}
            <div class="vr-card bg-[#2B2D31] rounded-lg p-4 mb-6">
                <h2 class="text-sm font-semibold mb-1">Добавить в друзья</h2>
                <p class="text-xs text-gray-400 mb-3">Можно добавить друга по его имени пользователя.</p>
                <form method="POST" action="{{ route('friends.store') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="username" placeholder="Введите имя пользователя" required
                           class="flex-1 bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none">
                    <button type="submit" class="btn-lift bg-[#5865F2] hover:bg-[#4752c4] px-4 py-2 rounded text-sm font-medium whitespace-nowrap">
                        Отправить заявку
                    </button>
                </form>
                @error('username')
                    <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Входящие заявки --}}
            @if ($incoming->count())
                <h3 class="text-xs font-semibold uppercase text-gray-400 mb-2">Входящие заявки — {{ $incoming->count() }}</h3>
                <div class="space-y-1 mb-6">
                    @foreach ($incoming as $request)
                        <div class="vr-card flex items-center gap-3 bg-[#2B2D31] rounded-lg px-3 py-2">
                            <img src="{{ $request->requester->avatar_url }}" class="w-9 h-9 rounded-full">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $request->requester->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ '@' . $request->requester->username }}</p>
                            </div>
                            <form method="POST" action="{{ route('friends.accept', $request->requester) }}">
                                @csrf
                                <button class="btn-lift w-9 h-9 rounded-full bg-[#3a3c42] hover:bg-emerald-600 flex items-center justify-center" title="Принять">✓</button>
                            </form>
                            <form method="POST" action="{{ route('friends.decline', $request->requester) }}">
                                @csrf
                                <button class="btn-lift w-9 h-9 rounded-full bg-[#3a3c42] hover:bg-red-600 flex items-center justify-center" title="Отклонить">✕</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Исходящие заявки --}}
            @if ($outgoing->count())
                <h3 class="text-xs font-semibold uppercase text-gray-400 mb-2">Исходящие заявки — {{ $outgoing->count() }}</h3>
                <div class="space-y-1 mb-6">
                    @foreach ($outgoing as $request)
                        <div class="vr-card flex items-center gap-3 bg-[#2B2D31] rounded-lg px-3 py-2">
                            <img src="{{ $request->recipient->avatar_url }}" class="w-9 h-9 rounded-full opacity-70">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $request->recipient->name }}</p>
                                <p class="text-xs text-gray-400 truncate">Заявка отправлена</p>
                            </div>
                            <form method="POST" action="{{ route('friends.decline', $request->recipient) }}">
                                @csrf
                                <button class="btn-lift text-xs text-gray-400 hover:text-red-400 px-2 py-1 rounded">Отменить</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Список друзей --}}
            <h3 class="text-xs font-semibold uppercase text-gray-400 mb-2">Все друзья — {{ $friends->count() }}</h3>
            @if ($friends->isEmpty())
                <p class="text-sm text-gray-400">Пока никого нет. Добавьте друзей по имени пользователя выше.</p>
            @else
                <div class="space-y-1">
                    @foreach ($friends as $friend)
                        <div class="flex items-center gap-3 bg-[#2B2D31] hover:bg-[#35373c] rounded-lg px-3 py-2 cursor-pointer transition-colors"
                             onclick="openProfile({{ $friend->id }}, event)">
                            <div class="relative">
                                <img src="{{ $friend->avatar_url }}" class="w-9 h-9 rounded-full">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-[#2B2D31] {{ $friend->isOnline() ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $friend->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $friend->isOnline() ? 'В сети' : 'Не в сети' }}</p>
                            </div>
                            <form method="POST" action="{{ route('friends.destroy', $friend) }}" onclick="event.stopPropagation()">
                                @csrf
                                @method('DELETE')
                                <button class="btn-lift text-xs text-gray-400 hover:text-red-400 px-2 py-1 rounded" onclick="return confirm('Удалить {{ $friend->name }} из друзей?')">Удалить</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </main>
</div>

{{-- Модалка присоединения к серверу по коду (нужна серверу и с этой страницы) --}}
<div x-data="{ show: false }" @open-join-modal.window="show = true">
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/60 vr-backdrop flex items-center justify-center z-50"
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="show = false">
        <div @click.outside="show = false" class="bg-[#313338] rounded-lg p-6 w-96 shadow-2xl"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <h3 class="font-semibold mb-3">Присоединиться к серверу</h3>
            <form method="POST" action="{{ route('servers.join') }}">
                @csrf
                <input type="text" name="invite_code" placeholder="Введите код приглашения"
                       class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-3 transition-shadow focus:ring-2 focus:ring-[#5865F2]" required>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="show = false" class="btn-lift text-sm text-gray-400 hover:text-white px-3 py-2 rounded">Отмена</button>
                    <button type="submit" class="btn-lift text-sm bg-[#5865F2] hover:bg-[#4752c4] px-4 py-2 rounded font-medium">Войти</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.profile-popover')
@include('components.profile-settings-modal')
@endsection
