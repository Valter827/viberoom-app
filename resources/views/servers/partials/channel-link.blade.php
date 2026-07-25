@php
    $isActive = isset($activeChannel) && $activeChannel && $activeChannel->id === $channel->id;
    $canManageChannel = isset($myRole) && in_array($myRole, ['owner', 'admin']);
@endphp
<div class="relative"
     x-data="{
        renaming: false,
        newName: @js($channel->name),
        async saveRename() {
            const name = this.newName.trim();
            if (!name) return;
            const res = await fetch('{{ route('channels.update', [$channel->server_id, $channel]) }}', {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name }),
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert('Не удалось переименовать канал.');
            }
        },
     }"
     @keydown.escape.window="$store.contextMenu.close('channel-{{ $channel->id }}'); renaming = false">

    <a href="{{ route('channels.show', [$channel->server_id, $channel]) }}"
       @contextmenu.prevent="$store.contextMenu.open('channel-{{ $channel->id }}', $event.clientX, $event.clientY)"
       class="flex items-center px-2 py-1.5 rounded text-sm text-gray-300 hover:bg-[#3a3c42] hover:text-white transition-colors
              {{ $isActive ? 'bg-[#404249] text-white' : '' }}">
        <span class="mr-1.5 text-gray-500">{{ $channel->isVoice() ? '🔊' : '#' }}</span>
        <span class="truncate">{{ $channel->name }}</span>
    </a>

    {{-- Контекстное меню канала (правый клик).
         Телепортируется в <body> — иначе overflow-y-auto списка каналов
         обрезает меню, если оно выходит за границы узкой колонки. --}}
    <template x-teleport="body">
    <div x-show="$store.contextMenu.openId === 'channel-{{ $channel->id }}' && !renaming" x-cloak
         x-transition:enter="ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @click="$store.contextMenu.close()"
         @click.outside="$store.contextMenu.close('channel-{{ $channel->id }}')"
         :style="`top: ${$store.contextMenu.y}px; left: ${$store.contextMenu.x}px;`"
         class="fixed z-50 w-60 bg-[#111214] rounded-lg shadow-2xl py-1.5 text-sm origin-top-left">
        <p class="px-3 py-1.5 text-xs font-semibold text-gray-500 truncate">
            {{ $channel->isVoice() ? '🔊' : '#' }} {{ $channel->name }}
        </p>

        <button type="button"
                @click="navigator.clipboard.writeText(window.location.origin + '{{ route('channels.show', [$channel->server_id, $channel], false) }}'); $dispatch('notify', 'Ссылка на канал скопирована')"
                class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
            🔗 Скопировать ссылку на канал
        </button>

        @if ($canManageChannel)
            <button type="button"
                    @click="$store.contextMenu.close(); renaming = true; $nextTick(() => $refs.renameInput.focus())"
                    class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                ✏️ Изменить название
            </button>

            <div class="my-1 border-t border-black/30"></div>

            <form method="POST" action="{{ route('channels.destroy', [$channel->server_id, $channel]) }}"
                  onsubmit="return confirm('Удалить канал «{{ $channel->name }}» вместе со всеми сообщениями?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full text-left px-3 py-1.5 text-red-400 hover:bg-red-500 hover:text-white">
                    🗑️ Удалить канал
                </button>
            </form>
        @endif
    </div>
    </template>

    {{-- Панель переименования канала (тоже телепортируется — та же причина) --}}
    @if ($canManageChannel)
        <template x-teleport="body">
        <div x-show="renaming" x-cloak
             @click.outside="renaming = false"
             :style="`top: ${$store.contextMenu.y}px; left: ${$store.contextMenu.x}px;`"
             class="fixed z-50 w-64 bg-[#111214] rounded-lg shadow-2xl p-3 text-sm origin-top-left">
            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название канала</label>
            <input x-ref="renameInput" x-model="newName"
                   @keydown.enter="saveRename()" @keydown.escape="renaming = false"
                   type="text" maxlength="50"
                   class="w-full bg-[#1E1F22] rounded px-2 py-1.5 text-sm outline-none mb-2">
            <div class="flex justify-end gap-2">
                <button type="button" @click="renaming = false" class="px-3 py-1 text-xs text-gray-400 hover:text-white">Отмена</button>
                <button type="button" @click="saveRename()" class="px-3 py-1 text-xs bg-[#5865F2] hover:bg-[#4752c4] rounded">Сохранить</button>
            </div>
        </div>
        </template>
    @endif

    @if ($channel->isVoice())
        {{-- Участники голосового канала, подгружаются из общего voiceParticipants (см. channel-sidebar.blade.php) --}}
        <template x-if="voiceParticipants[{{ $channel->id }}]?.length">
            <div class="pl-6 pb-1 space-y-0.5">
                <template x-for="p in (voiceParticipants[{{ $channel->id }}] || [])" :key="p.user_id">
                    <div class="flex items-center gap-1.5 py-0.5">
                        <div class="relative">
                            <img :src="p.avatar_url" class="w-5 h-5 rounded-full transition-all duration-100"
                                 :class="($store.voice.channelId === {{ $channel->id }} && $store.voice.speaking[p.user_id]) ? 'ring-2 ring-emerald-500' : ''">
                            <span x-show="p.muted" class="absolute -bottom-0.5 -right-0.5 text-[8px]">🔇</span>
                        </div>
                        <span class="text-xs text-gray-400 truncate" x-text="p.name"></span>
                    </div>
                </template>
            </div>
        </template>
    @endif
</div>
