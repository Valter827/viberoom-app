{{-- Голосовой канал: presence-список + WebRTC P2P звонок (состояние живёт в глобальном Alpine.store('voice')) --}}
<div class="flex-1 flex flex-col bg-[#313338]">
    <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0 gap-2">
        <span class="text-gray-400 mr-1.5">🔊</span>
        <h2 class="font-semibold">{{ $activeChannel->name }}</h2>
        <template x-if="$store.voice.joined && $store.voice.channelId === {{ $activeChannel->id }}">
            <span class="text-xs text-emerald-400 flex items-center gap-1 ml-2">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span x-text="$store.voice.formattedDuration"></span>
            </span>
        </template>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center gap-6 p-6">
        <template x-if="!$store.voice.joined || $store.voice.channelId !== {{ $activeChannel->id }}">
            <button
                @click="$store.voice.join({{ $activeChannel->id }}, {{ $server->id }}, {{ Js::from($activeChannel->name) }})"
                :disabled="$store.voice.connecting"
                class="btn-lift bg-[#5865F2] hover:bg-[#4752c4] disabled:opacity-50 px-6 py-3 rounded-lg font-medium">
                <span x-show="!$store.voice.connecting">🔊 Присоединиться к голосовому каналу</span>
                <span x-show="$store.voice.connecting">Подключение...</span>
            </button>
        </template>

        <template x-if="$store.voice.joined && $store.voice.channelId === {{ $activeChannel->id }}">
            <div class="w-full h-full flex flex-col">
                {{-- Плитки участников — крупные, как в видеозвонке --}}
                <div class="flex-1 grid gap-3 p-2 place-content-center"
                     :style="`grid-template-columns: repeat(${Math.min($store.voice.participants.length, 3)}, minmax(180px, 320px))`">
                    <template x-for="p in $store.voice.participants" :key="p.user_id">
                        <div class="vr-card relative bg-[#1a1b1e] rounded-xl aspect-video flex items-center justify-center transition-shadow duration-100 overflow-hidden"
                             :class="$store.voice.speaking[p.user_id] ? 'ring-2 ring-emerald-500' : ''">
                            <template x-if="$store.voice.videoStreams[p.user_id]">
                                <video autoplay playsinline muted
                                       class="w-full h-full object-cover"
                                       :class="$store.voice.screenSharingUsers[p.user_id] ? 'object-contain bg-black' : 'object-cover'"
                                       x-effect="$el.srcObject = $store.voice.videoStreams[p.user_id] || null"></video>
                            </template>
                            <template x-if="!$store.voice.videoStreams[p.user_id]">
                                <img :src="p.avatar_url" class="w-20 h-20 rounded-full">
                            </template>
                            <span class="absolute bottom-2 left-2 text-xs bg-black/50 px-2 py-1 rounded flex items-center gap-1">
                                <span x-show="$store.voice.screenSharingUsers[p.user_id]">🖥️</span>
                                <span x-text="p.name"></span>
                            </span>
                            <span x-show="p.muted" class="absolute bottom-2 right-2 bg-red-600 rounded-full w-6 h-6 flex items-center justify-center text-xs">🔇</span>
                        </div>
                    </template>
                </div>

                {{-- Панель управления снизу, как в référence --}}
                <div class="flex justify-center gap-3 py-4">
                    <button @click="$store.voice.toggleMute()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.muted ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Микрофон">
                        <span x-text="$store.voice.muted ? '🔇' : '🎙️'"></span>
                    </button>
                    <button @click="$store.voice.toggleDeafen()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.deafened ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Звук">
                        <span x-text="$store.voice.deafened ? '🔕' : '🔔'"></span>
                    </button>
                    <button @click="$store.voice.toggleCamera()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.cameraEnabled ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Камера">
                        📹
                    </button>
                    <button @click="$store.voice.toggleScreenShare()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.screenSharing ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Демонстрация экрана">
                        🖥️
                    </button>
                    <button x-data @click="$dispatch('open-voice-settings')" class="icon-gear btn-lift w-11 h-11 rounded-full flex items-center justify-center bg-[#3a3c42] hover:bg-[#43454b]" title="Настройки голоса">
                        ⚙️
                    </button>
                    <button @click="$store.voice.leave()" class="btn-lift w-14 h-11 rounded-full flex items-center justify-center bg-red-600 hover:bg-red-500" title="Покинуть канал">
                        📞
                    </button>
                </div>

                <p class="text-xs text-gray-500 text-center pb-4 max-w-md mx-auto">
                    Звонок работает напрямую между браузерами (P2P). Можно перейти в текстовый
                    канал написать сообщение — звонок останется активным (см. полосу внизу слева).
                </p>
            </div>
        </template>
    </div>
</div>
