@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden" x-data x-init="if (window.top !== window.self) $el.querySelector('#server-icon-rail')?.remove()">
    <div id="server-icon-rail">
        @include('servers.partials.server-sidebar')
    </div>

    <main class="flex-1 flex bg-[#313338] overflow-hidden"
          x-data="{ tab: '{{ in_array($myRole, ['owner','admin']) ? 'general' : 'members' }}' }">

        {{-- Левая колонка: навигация по разделам настроек --}}
        <nav class="w-56 flex-shrink-0 bg-[#2B2D31] overflow-y-auto py-6 px-3"
             x-data
             x-init="
                if (window.top !== window.self) {
                    // страница открыта внутри окна-модалки настроек — 'Назад' закрывает окно, а не листает iframe
                    $refs.backLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        window.parent.postMessage('close-server-settings', '*');
                    });
                }
             ">
            <a x-ref="backLink" href="{{ route('servers.show', $server) }}" class="btn-lift flex items-center gap-2 text-gray-400 hover:text-white text-sm px-2 py-1.5 rounded-md hover:bg-[#35373c] mb-4">
                ← Назад на сервер
            </a>

            @if (in_array($myRole, ['owner', 'admin']))
                <p class="text-xs font-semibold uppercase text-gray-500 px-2 mb-1 mt-2">Сервер</p>
                <button @click="tab = 'general'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                        :class="tab === 'general' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                    Профиль сервера
                </button>
                <button @click="tab = 'features'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                        :class="tab === 'features' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                    Функции
                </button>
            @endif

            <p class="text-xs font-semibold uppercase text-gray-500 px-2 mb-1 mt-4">Люди</p>
            <button @click="tab = 'members'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                    :class="tab === 'members' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                Участники
            </button>

            @if (in_array($myRole, ['owner', 'admin']))
                <button @click="tab = 'bans'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                        :class="tab === 'bans' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                    Баны
                </button>
            @endif

            {{-- Выйти/удалить сервер — работает и на обычной странице, и внутри iframe-модалки
                 настроек (там обычный редирект просто перезагрузил бы содержимое самого iframe,
                 поэтому здесь запрос уходит через fetch, а переход на дашборд делается через
                 window.top, чтобы вырваться из окна). --}}
            <div class="mt-6 pt-4 border-t border-black/20"
                 x-data="{
                    busy: false,
                    async leaveOrDelete(url, confirmText) {
                        if (!confirm(confirmText) || this.busy) return;
                        this.busy = true;
                        try {
                            const res = await fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                },
                            });
                            if (!res.ok) { this.busy = false; alert('Не удалось выполнить действие.'); return; }
                            const target = '{{ route('dashboard') }}';
                            (window.top !== window.self ? window.top : window).location.href = target;
                        } catch (e) {
                            this.busy = false;
                            alert('Не удалось выполнить действие.');
                        }
                    },
                 }">
                @if ($myRole === 'owner')
                    <button type="button" :disabled="busy"
                            @click="leaveOrDelete('{{ route('servers.destroy', $server) }}', 'Удалить сервер «{{ $server->name }}» без возможности восстановления?')"
                            class="w-full text-left px-2 py-1.5 rounded text-sm text-red-400 hover:bg-red-500/10 disabled:opacity-50">
                        🗑️ Удалить сервер
                    </button>
                @else
                    <button type="button" :disabled="busy"
                            @click="leaveOrDelete('{{ route('servers.leave', $server) }}', 'Выйти с сервера «{{ $server->name }}»?')"
                            class="w-full text-left px-2 py-1.5 rounded text-sm text-red-400 hover:bg-red-500/10 disabled:opacity-50">
                        🚪 Покинуть сервер
                    </button>
                @endif
            </div>
        </nav>

        {{-- Центр: содержимое активной вкладки --}}
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-2xl">

                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/40 px-4 py-3">
                        <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-500/10 border border-red-500/40 px-4 py-3">
                        <ul class="text-xs text-red-300 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Профиль сервера --}}
                @if (in_array($myRole, ['owner', 'admin']))
                <div x-show="tab === 'general'" x-cloak>
                    <h1 class="text-xl font-bold mb-1">Профиль сервера</h1>
                    <p class="text-sm text-gray-400 mb-6">Настройте, как выглядит ваш сервер для участников и в приглашениях.</p>

                    <form method="POST" action="{{ route('servers.update', $server) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="flex items-center gap-4 mb-6">
                            <img src="{{ $server->iconUrl() }}" class="w-20 h-20 rounded-full object-cover">
                            <div>
                                <label class="btn-lift cursor-pointer inline-block text-sm bg-[#4752c4] hover:bg-[#5865F2] px-4 py-2 rounded font-medium">
                                    Изменить значок сервера
                                    <input type="file" name="icon" accept="image/*" class="hidden">
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Рекомендуем изображение минимум 512×512.</p>
                            </div>
                        </div>

                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название</label>
                        <input type="text" name="name" value="{{ old('name', $server->name) }}" required maxlength="100"
                               class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">

                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Описание</label>
                        <textarea name="description" rows="3" maxlength="300"
                                  class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4 resize-none"
                                  placeholder="Почему вы создали этот сервер? Зачем пользователям к нему присоединяться?">{{ old('description', $server->description) }}</textarea>

                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Код приглашения</label>
                        <div class="flex items-center gap-2 mb-6">
                            <input type="text" value="{{ $server->invite_code }}" readonly
                                   class="flex-1 bg-[#1E1F22] rounded px-3 py-2 text-sm text-gray-400 outline-none">
                            <button type="button" x-data @click="navigator.clipboard.writeText('{{ $server->invite_code }}')"
                                    class="btn-lift text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-2 rounded">Копировать</button>
                        </div>

                        <button type="submit" class="btn-lift bg-[#5865F2] hover:bg-[#4752c4] rounded px-5 py-2 text-sm font-medium">
                            Сохранить изменения
                        </button>
                    </form>
                </div>
                @endif

                {{-- Функции --}}
                @if (in_array($myRole, ['owner', 'admin']))
                <div x-show="tab === 'features'" x-cloak>
                    <h1 class="text-xl font-bold mb-1">Функции сервера</h1>
                    <p class="text-sm text-gray-400 mb-6">Включайте или выключайте экспериментальные фичи для всех участников сервера.</p>

                    <form method="POST" action="{{ route('servers.features.update', $server) }}">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-3">
                            <label class="vr-card flex items-start gap-3 bg-[#2B2D31] rounded-lg p-4 cursor-pointer">
                                <input type="checkbox" name="vibe_match_enabled" value="1" {{ $server->vibe_match_enabled ? 'checked' : '' }}
                                       class="mt-1 w-4 h-4 accent-[#5865F2]">
                                <div>
                                    <p class="text-sm font-semibold">🎯 Vibe Match — совпадение интересов</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Подсвечивает в чате, если у нескольких участников в комнате сейчас совпадают интересы (например, оба ищут во что поиграть или слушают одно и то же).</p>
                                </div>
                            </label>

                            <label class="vr-card flex items-start gap-3 bg-[#2B2D31] rounded-lg p-4 cursor-pointer">
                                <input type="checkbox" name="party_finder_enabled" value="1" {{ $server->party_finder_enabled ? 'checked' : '' }}
                                       class="mt-1 w-4 h-4 accent-[#5865F2]">
                                <div>
                                    <p class="text-sm font-semibold">🎮 Party Finder — карточка пати</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Позволяет создавать в чате интерактивные карточки сбора команды со слотами вместо сообщений "кто в катку?".</p>
                                </div>
                            </label>

                            <label class="vr-card flex items-start gap-3 bg-[#2B2D31] rounded-lg p-4 cursor-pointer">
                                <input type="checkbox" name="tactical_canvas_enabled" value="1" {{ $server->tactical_canvas_enabled ? 'checked' : '' }}
                                       class="mt-1 w-4 h-4 accent-[#5865F2]">
                                <div>
                                    <p class="text-sm font-semibold">🗺️ Tactical Canvas — тактический оверлей</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Добавляет выдвижную мини-доску поверх чата с заготовками карт (Dota, CS, Valorant, Rust) для набросков тактики в реальном времени.</p>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn-lift bg-[#5865F2] hover:bg-[#4752c4] rounded px-5 py-2 text-sm font-medium mt-5">
                            Сохранить изменения
                        </button>
                    </form>
                </div>
                @endif

                {{-- Участники --}}
                <div x-show="tab === 'members'" x-cloak>
                    <h1 class="text-xl font-bold mb-1">Участники — {{ $server->members->count() }}</h1>
                    <p class="text-sm text-gray-400 mb-6">Управляйте ролями и модерацией участников сервера.</p>

                    <div class="vr-card bg-[#2B2D31] rounded-lg divide-y divide-black/20 overflow-hidden">
                        @foreach ($server->members as $member)
                            @php $role = $member->pivot->role; @endphp
                            <div class="flex items-center gap-3 px-3 py-2.5 hover:bg-white/[0.03] transition-colors">
                                <img src="{{ $member->avatar_url }}" class="w-9 h-9 rounded-full">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $member->name }}</p>
                                    <p class="text-xs text-gray-500">@{{ $member->username }}</p>
                                </div>

                                <span class="text-xs px-2 py-1 rounded-full
                                    {{ $role === 'owner' ? 'bg-yellow-500/20 text-yellow-400' : ($role === 'admin' ? 'bg-red-500/20 text-red-400' : ($role === 'moderator' ? 'bg-blue-500/20 text-blue-400' : 'bg-gray-500/20 text-gray-400')) }}">
                                    {{ ['owner' => 'Владелец', 'admin' => 'Админ', 'moderator' => 'Модератор', 'member' => 'Участник'][$role] ?? $role }}
                                </span>

                                @if (in_array($myRole, ['owner', 'admin']) && $role !== 'owner' && $member->id !== Auth::id())
                                    <form method="POST" action="{{ route('members.role', [$server, $member]) }}" class="ml-1">
                                        @csrf @method('PATCH')
                                        <select name="role" onchange="this.form.submit()" class="bg-[#1E1F22] text-xs rounded px-2 py-1.5 outline-none">
                                            <option value="member" {{ $role === 'member' ? 'selected' : '' }}>Участник</option>
                                            <option value="moderator" {{ $role === 'moderator' ? 'selected' : '' }}>Модератор</option>
                                            <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Админ</option>
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('members.kick', [$server, $member]) }}" onsubmit="return confirm('Исключить {{ $member->name }} с сервера?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-lift text-xs text-gray-400 hover:text-white px-2 py-1 rounded" title="Кикнуть">👢</button>
                                    </form>
                                    <form method="POST" action="{{ route('members.ban', [$server, $member]) }}" onsubmit="return confirm('Забанить {{ $member->name }} на этом сервере?')">
                                        @csrf
                                        <button class="btn-lift text-xs text-red-400 hover:text-red-300 px-2 py-1 rounded" title="Забанить">🔨</button>
                                    </form>
                                @elseif (in_array($myRole, ['owner', 'admin', 'moderator']) && $role === 'member' && $member->id !== Auth::id())
                                    <form method="POST" action="{{ route('members.kick', [$server, $member]) }}" onsubmit="return confirm('Исключить {{ $member->name }} с сервера?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-lift text-xs text-gray-400 hover:text-white px-2 py-1 rounded" title="Кикнуть">👢</button>
                                    </form>
                                    <form method="POST" action="{{ route('members.ban', [$server, $member]) }}" onsubmit="return confirm('Забанить {{ $member->name }} на этом сервере?')">
                                        @csrf
                                        <button class="btn-lift text-xs text-red-400 hover:text-red-300 px-2 py-1 rounded" title="Забанить">🔨</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Баны --}}
                @if (in_array($myRole, ['owner', 'admin']))
                <div x-show="tab === 'bans'" x-cloak>
                    <h1 class="text-xl font-bold mb-1">Забаненные пользователи</h1>
                    <p class="text-sm text-gray-400 mb-6">Эти пользователи не могут зайти на сервер даже по коду приглашения.</p>

                    <div class="vr-card bg-[#2B2D31] rounded-lg divide-y divide-black/20 overflow-hidden">
                        @forelse ($server->bans as $ban)
                            <div class="flex items-center gap-3 px-3 py-2.5 hover:bg-white/[0.03] transition-colors">
                                <img src="{{ $ban->user->avatar_url }}" class="w-9 h-9 rounded-full opacity-60">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $ban->user->name }}</p>
                                    <p class="text-xs text-gray-500">@{{ $ban->user->username }}</p>
                                </div>
                                <form method="POST" action="{{ route('members.unban', [$server, $ban->user]) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn-lift text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-1.5 rounded">Разбанить</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 px-3 py-3">Забаненных пока нет.</p>
                        @endforelse
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Правая колонка: живое превью карточки сервера (как в Discord) --}}
        @if (in_array($myRole, ['owner', 'admin']))
        <aside class="w-72 flex-shrink-0 bg-[#2B2D31] p-4 hidden xl:block" x-show="tab === 'general'" x-cloak>
            <p class="text-xs font-semibold uppercase text-gray-400 mb-2">Предпросмотр</p>
            <div class="vr-card rounded-xl overflow-hidden bg-[#1E1F22]">
                <div class="h-16 bg-gradient-to-b from-black/40 to-transparent bg-[#5865F2]"></div>
                <div class="p-3 -mt-8">
                    <img src="{{ $server->iconUrl() }}" class="w-16 h-16 rounded-full border-4 border-[#1E1F22] object-cover mb-2">
                    <p class="font-bold">{{ $server->name }}</p>
                    <p class="text-xs text-gray-400 flex items-center gap-1 mt-1">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        {{ $server->members->filter(fn($m) => $m->isOnline())->count() }} в сети
                        <span class="text-gray-600">·</span>
                        {{ $server->members->count() }} участников
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Дата основания: {{ $server->created_at->translatedFormat('F Y') }}</p>
                </div>
            </div>
        </aside>
        @endif
    </main>
</div>
@endsection
