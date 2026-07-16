@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden" x-data="{ tab: '{{ in_array($myRole, ['owner','admin']) ? 'general' : 'members' }}' }">
    @include('servers.partials.server-sidebar')

    <main class="flex-1 flex flex-col bg-[#313338] overflow-y-auto">
        <div class="h-12 flex items-center px-4 border-b border-black/20 flex-shrink-0 gap-3">
            <a href="{{ route('servers.show', $server) }}" class="text-gray-400 hover:text-white text-sm">← Назад</a>
            <span class="font-semibold">Настройки сервера — {{ $server->name }}</span>
        </div>

        <div class="p-6 max-w-2xl mx-auto w-full">

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

            {{-- Вкладки --}}
            <div class="flex gap-1 mb-4 border-b border-black/20">
                @if (in_array($myRole, ['owner', 'admin']))
                    <button @click="tab = 'general'" class="px-3 py-2 text-sm" :class="tab === 'general' ? 'text-white border-b-2 border-[#5865F2]' : 'text-gray-400'">Общее</button>
                @endif
                <button @click="tab = 'members'" class="px-3 py-2 text-sm" :class="tab === 'members' ? 'text-white border-b-2 border-[#5865F2]' : 'text-gray-400'">Участники</button>
                @if (in_array($myRole, ['owner', 'admin']))
                    <button @click="tab = 'bans'" class="px-3 py-2 text-sm" :class="tab === 'bans' ? 'text-white border-b-2 border-[#5865F2]' : 'text-gray-400'">Забаненные</button>
                @endif
            </div>

            {{-- Вкладка: общие настройки --}}
            @if (in_array($myRole, ['owner', 'admin']))
            <div x-show="tab === 'general'" x-cloak class="bg-[#2B2D31] rounded-lg p-6">
                <form method="POST" action="{{ route('servers.update', $server) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-center gap-4 mb-5">
                        <img src="{{ $server->iconUrl() }}" class="w-16 h-16 rounded-full object-cover">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Иконка сервера</label>
                            <input type="file" name="icon" accept="image/*" class="text-sm text-gray-300">
                        </div>
                    </div>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название сервера</label>
                    <input type="text" name="name" value="{{ old('name', $server->name) }}" required maxlength="100"
                           class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Описание сервера</label>
                    <textarea name="description" rows="3" maxlength="300"
                              class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4 resize-none"
                              placeholder="Расскажите, о чём этот сервер...">{{ old('description', $server->description) }}</textarea>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Код приглашения</label>
                    <div class="flex items-center gap-2 mb-5">
                        <input type="text" value="{{ $server->invite_code }}" readonly
                               class="flex-1 bg-[#1E1F22] rounded px-3 py-2 text-sm text-gray-400 outline-none">
                        <button type="button" x-data @click="navigator.clipboard.writeText('{{ $server->invite_code }}')"
                                class="text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-2 rounded">Копировать</button>
                    </div>

                    <button type="submit" class="w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                        Сохранить изменения
                    </button>
                </form>
            </div>
            @endif

            {{-- Вкладка: участники --}}
            <div x-show="tab === 'members'" x-cloak class="bg-[#2B2D31] rounded-lg p-4 space-y-2">
                @foreach ($server->members as $member)
                    @php $role = $member->pivot->role; @endphp
                    <div class="flex items-center gap-3 px-2 py-2 rounded hover:bg-white/[0.03]">
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
                                <button class="text-xs text-gray-400 hover:text-white px-2" title="Кикнуть">👢</button>
                            </form>

                            <form method="POST" action="{{ route('members.ban', [$server, $member]) }}" onsubmit="return confirm('Забанить {{ $member->name }} на этом сервере?')">
                                @csrf
                                <button class="text-xs text-red-400 hover:text-red-300 px-2" title="Забанить">🔨</button>
                            </form>
                        @elseif (in_array($myRole, ['owner', 'admin', 'moderator']) && $role === 'member' && $member->id !== Auth::id())
                            {{-- модератор может кикать/банить только member'ов, но не менять роли --}}
                            <form method="POST" action="{{ route('members.kick', [$server, $member]) }}" onsubmit="return confirm('Исключить {{ $member->name }} с сервера?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-gray-400 hover:text-white px-2" title="Кикнуть">👢</button>
                            </form>
                            <form method="POST" action="{{ route('members.ban', [$server, $member]) }}" onsubmit="return confirm('Забанить {{ $member->name }} на этом сервере?')">
                                @csrf
                                <button class="text-xs text-red-400 hover:text-red-300 px-2" title="Забанить">🔨</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Вкладка: забаненные --}}
            @if (in_array($myRole, ['owner', 'admin']))
            <div x-show="tab === 'bans'" x-cloak class="bg-[#2B2D31] rounded-lg p-4 space-y-2">
                @forelse ($server->bans as $ban)
                    <div class="flex items-center gap-3 px-2 py-2 rounded hover:bg-white/[0.03]">
                        <img src="{{ $ban->user->avatar_url }}" class="w-9 h-9 rounded-full opacity-60">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $ban->user->name }}</p>
                            <p class="text-xs text-gray-500">@{{ $ban->user->username }}</p>
                        </div>
                        <form method="POST" action="{{ route('members.unban', [$server, $ban->user]) }}">
                            @csrf @method('DELETE')
                            <button class="text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-1.5 rounded">Разбанить</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 px-2 py-2">Забаненных пока нет.</p>
                @endforelse
            </div>
            @endif
        </div>
    </main>
</div>
@endsection
