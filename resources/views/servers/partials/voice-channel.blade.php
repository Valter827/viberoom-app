{{-- Голосовой канал: presence-список + WebRTC P2P звонок (состояние живёт в глобальном Alpine.store('voice')) --}}
<div class="flex-1 flex flex-col bg-[#313338]">
    <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
        <span class="text-gray-400 mr-1.5">🔊</span>
        <h2 class="font-semibold">{{ $activeChannel->name }}</h2>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center gap-6 p-6">
        <template x-if="!$store.voice.joined || $store.voice.channelId !== {{ $activeChannel->id }}">
            <button
                @click="$store.voice.join({{ $activeChannel->id }}, {{ $server->id }}, {{ Js::from($activeChannel->name) }})"
                :disabled="$store.voice.connecting"
                class="bg-[#5865F2] hover:bg-[#4752c4] disabled:opacity-50 px-6 py-3 rounded-lg font-medium">
                <span x-show="!$store.voice.connecting">🔊 Присоединиться к голосовому каналу</span>
                <span x-show="$store.voice.connecting">Подключение...</span>
            </button>
        </template>

        <template x-if="$store.voice.joined && $store.voice.channelId === {{ $activeChannel->id }}">
            <div class="w-full max-w-2xl">
                <div class="flex flex-wrap justify-center gap-6 mb-8">
                    <template x-for="p in $store.voice.participants" :key="p.user_id">
                        <div class="flex flex-col items-center gap-2">
                            <div class="relative">
                                <img :src="p.avatar_url" class="w-20 h-20 rounded-full border-4 transition-colors duration-100"
                                     :class="$store.voice.speaking[p.user_id] ? 'border-emerald-500' : 'border-transparent'">
                                <span x-show="p.muted" class="absolute bottom-0 right-0 bg-red-600 rounded-full w-7 h-7 flex items-center justify-center text-xs">🔇</span>
                            </div>
                            <span class="text-sm text-gray-300" x-text="p.name"></span>
                        </div>
                    </template>
                </div>

                <div class="flex justify-center gap-3">
                    <button @click="$store.voice.toggleMute()" class="px-5 py-3 rounded-full"
                            :class="$store.voice.muted ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'">
                        <span x-text="$store.voice.muted ? '🔇 Микрофон выкл.' : '🎙️ Микрофон вкл.'"></span>
                    </button>
                    <button @click="$store.voice.toggleDeafen()" class="px-5 py-3 rounded-full"
                            :class="$store.voice.deafened ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'">
                        <span x-text="$store.voice.deafened ? '🔕 Звук выкл.' : '🔔 Звук вкл.'"></span>
                    </button>
                    <button @click="$store.voice.leave()" class="px-5 py-3 rounded-full bg-red-600 hover:bg-red-500">
                        📞 Покинуть канал
                    </button>
                </div>

                <p class="text-xs text-gray-500 text-center mt-6 max-w-md mx-auto">
                    Звонок работает напрямую между браузерами (P2P). Если кто-то за корпоративным
                    файрволом/жёстким NAT, соединение может не установиться. Можно спокойно перейти
                    в текстовый канал написать — звонок останется активным (см. полосу внизу слева).
                </p>
            </div>
        </template>
    </div>
</div>
