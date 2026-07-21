{{-- Вторая колонка: название сервера + список каналов, сгруппированных по категориям --}}
<aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">

    {{-- Шапка с названием сервера --}}
    <div class="h-12 flex items-center justify-between px-4 shadow-sm border-b border-black/20 flex-shrink-0">
        <h1 class="font-semibold truncate">{{ $server->name }}</h1>
        <div class="flex items-center gap-3">
            @php $myRole = $server->members->firstWhere('id', Auth::id())?->pivot->role; @endphp
            @include('components.mentions-bell')
            @if (in_array($myRole, ['owner', 'admin', 'moderator']))
                <button @click="$dispatch('open-server-settings', { url: '{{ route('servers.edit', $server) }}' })"
                        class="text-gray-400 hover:text-white text-xs" title="Участники и настройки">👥</button>
            @endif
            @if (in_array($myRole, ['owner', 'admin']))
                <button @click="$dispatch('open-server-settings', { url: '{{ route('servers.edit', $server) }}' })"
                        class="text-gray-400 hover:text-white text-xs" title="Настройки сервера">⚙️</button>
            @endif
            <button
                x-data
                @click="navigator.clipboard.writeText('{{ $server->invite_code }}'); $dispatch('notify', 'Код приглашения скопирован')"
                class="text-gray-400 hover:text-white text-xs" title="Скопировать код приглашения">
                📋
            </button>
        </div>
    </div>

    {{-- Список категорий и каналов --}}
    <div class="flex-1 overflow-y-auto px-2 py-3 space-y-4"
         x-data="{
            showCreate: false,
            targetCategoryId: null,
            voiceParticipants: {},
            async loadVoiceParticipants() {
                const res = await fetch('{{ route('voice.server-participants', $server) }}', { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.voiceParticipants = await res.json();
            },
         }"
         x-init="loadVoiceParticipants(); setInterval(() => loadVoiceParticipants(), 5000)"
         @voice-participants-changed.window="loadVoiceParticipants()"
         @open-create-channel.window="showCreate = true; targetCategoryId = $event.detail">
        @foreach ($server->categories as $category)
            <div>
                <div class="flex items-center justify-between px-2 mb-1 group">
                    <h2 class="text-xs font-semibold uppercase text-gray-400 tracking-wide">
                        {{ $category->name }}
                    </h2>
                    @if (in_array($myRole, ['owner', 'admin']))
                        <button @click="$dispatch('open-create-channel', {{ $category->id }})"
                                class="text-gray-500 hover:text-white opacity-0 group-hover:opacity-100 text-sm" title="Создать канал">+</button>
                    @endif
                </div>
                <div class="space-y-0.5">
                    @foreach ($category->channels as $channel)
                        @include('servers.partials.channel-link', ['channel' => $channel])
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Каналы без категории --}}
        <div>
            @if (in_array($myRole, ['owner', 'admin']))
                <div class="flex items-center justify-between px-2 mb-1 group">
                    <h2 class="text-xs font-semibold uppercase text-gray-400 tracking-wide">Каналы</h2>
                    <button @click="$dispatch('open-create-channel', null)"
                            class="text-gray-500 hover:text-white opacity-0 group-hover:opacity-100 text-sm" title="Создать канал">+</button>
                </div>
            @endif
            <div class="space-y-0.5">
                @foreach ($server->channels->whereNull('category_id') as $channel)
                    @include('servers.partials.channel-link', ['channel' => $channel])
                @endforeach
            </div>
        </div>

        {{-- Модалка создания канала --}}
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
            <div @click.outside="showCreate = false" class="bg-[#313338] rounded-lg p-6 w-96">
                <h3 class="font-semibold mb-4">Создать канал</h3>
                <form method="POST" action="{{ route('channels.store', $server) }}">
                    @csrf
                    <template x-if="targetCategoryId">
                        <input type="hidden" name="category_id" :value="targetCategoryId">
                    </template>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Тип канала</label>
                    <div class="flex gap-2 mb-4" x-data="{ type: 'text' }">
                        <label class="flex-1 flex items-center gap-2 bg-[#1E1F22] rounded px-3 py-2 cursor-pointer"
                               :class="type === 'text' ? 'ring-1 ring-[#5865F2]' : ''">
                            <input type="radio" name="type" value="text" x-model="type" class="accent-[#5865F2]">
                            <span class="text-sm">💬 Текстовый</span>
                        </label>
                        <label class="flex-1 flex items-center gap-2 bg-[#1E1F22] rounded px-3 py-2 cursor-pointer"
                               :class="type === 'voice' ? 'ring-1 ring-[#5865F2]' : ''">
                            <input type="radio" name="type" value="voice" x-model="type" class="accent-[#5865F2]">
                            <span class="text-sm">🔊 Голосовой</span>
                        </label>
                    </div>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название канала</label>
                    <input type="text" name="name" required maxlength="50" placeholder="новый-канал"
                           class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreate = false" class="text-sm text-gray-400 hover:text-white">Отмена</button>
                        <button type="submit" class="text-sm bg-[#5865F2] hover:bg-[#4752c4] px-4 py-2 rounded">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Постоянная полоса активного голосового звонка — видна на любой странице сервера,
         не пропадает при переходе в текстовый канал написать сообщение --}}
    <div x-show="$store.voice.joined" x-cloak class="bg-[#232428] border-t border-black/20 px-3 py-2 flex-shrink-0">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-1.5 text-emerald-400 text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Голосовая связь подключена
                <span class="text-gray-400" x-text="'· ' + $store.voice.formattedDuration"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="$store.voice.leave()" class="text-gray-400 hover:text-red-400 text-sm" title="Отключиться">📞</button>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-1.5 truncate">🔊 <span x-text="$store.voice.channelName"></span></p>
        <div class="flex gap-1.5 relative" x-data="{ showQuickSettings: false }">
            <div class="flex-1 flex rounded overflow-hidden" :class="$store.voice.muted ? 'bg-red-600/80' : 'bg-[#3a3c42]'">
                <button @click="$store.voice.toggleMute()" class="flex-1 text-xs py-1 hover:bg-black/10">
                    <span x-text="$store.voice.muted ? '🔇' : '🎙️'"></span>
                </button>
                <button @click="showQuickSettings = !showQuickSettings" class="px-1.5 hover:bg-black/10 border-l border-black/20 text-[10px]" title="Настройки голоса">▲</button>
            </div>
            <button @click="$store.voice.toggleDeafen()"
                    class="flex-1 text-xs rounded py-1"
                    :class="$store.voice.deafened ? 'bg-red-600/80' : 'bg-[#3a3c42] hover:bg-[#43454b]'">
                <span x-text="$store.voice.deafened ? '🔕' : '🔔'"></span>
            </button>
            <a :href="`/servers/${$store.voice.serverId}/channels/${$store.voice.channelId}`"
               class="flex-1 text-xs rounded py-1 bg-[#3a3c42] hover:bg-[#43454b] text-center" title="Вернуться к голосовому каналу">↩️</a>

            {{-- Компактная всплывающая панель быстрых настроек голоса, как в Discord --}}
            <div x-show="showQuickSettings" x-cloak @click.outside="showQuickSettings = false"
                 class="absolute bottom-full left-0 mb-2 w-72 bg-[#111214] rounded-lg shadow-2xl p-3 z-30 text-sm">
                @include('components.voice-quick-panel')
            </div>
        </div>
    </div>

    {{-- Мини-профиль текущего пользователя внизу колонки --}}
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
        <a href="{{ route('profile.edit') }}" class="ml-auto text-gray-400 hover:text-white text-sm" title="Настройки профиля">⚙️</a>
    </div>
</aside>

@include('components.voice-settings-modal')
@include('components.server-settings-modal')
