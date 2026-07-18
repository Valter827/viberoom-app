@php
    $isActive = isset($activeChannel) && $activeChannel && $activeChannel->id === $channel->id;
@endphp
<div>
    <a href="{{ route('channels.show', [$channel->server_id, $channel]) }}"
       class="flex items-center px-2 py-1.5 rounded text-sm text-gray-300 hover:bg-[#3a3c42] hover:text-white transition-colors
              {{ $isActive ? 'bg-[#404249] text-white' : '' }}">
        <span class="mr-1.5 text-gray-500">{{ $channel->isVoice() ? '🔊' : '#' }}</span>
        <span class="truncate">{{ $channel->name }}</span>
    </a>

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
