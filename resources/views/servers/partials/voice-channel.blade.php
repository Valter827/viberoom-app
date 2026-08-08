{{-- Голосовой канал: presence-список + WebRTC P2P звонок (состояние живёт в глобальном Alpine.store('voice')) --}}
<div class="flex-1 flex flex-col bg-[#313338]">
    <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0 gap-2">
        <x-icon name="volume-2" class="w-4 h-4 text-gray-400 mr-1.5" />
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
                <span x-show="!$store.voice.connecting" class="flex items-center gap-2"><x-icon name="phone" class="w-5 h-5" /> Присоединиться к голосовому каналу</span>
                <span x-show="$store.voice.connecting">Подключение...</span>
            </button>
        </template>

        <template x-if="$store.voice.joined && $store.voice.channelId === {{ $activeChannel->id }}">
            <div class="w-full h-full flex flex-col" x-data="{ expandedUser: null }" @keydown.escape.window="expandedUser = null">
                {{-- Плитки участников — крупные, как в видеозвонке --}}
                <div class="flex-1 grid gap-3 p-2 place-content-center"
                     :style="`grid-template-columns: repeat(${Math.min($store.voice.participants.length, 3)}, minmax(180px, 320px))`">
                    <template x-for="p in $store.voice.participants" :key="p.user_id">
                        <div @click="expandedUser = p.user_id"
                             class="vr-card relative bg-[#1a1b1e] rounded-xl aspect-video flex items-center justify-center transition-shadow duration-100 overflow-hidden cursor-pointer hover:ring-2 hover:ring-white/20"
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
                                <x-icon name="monitor" class="w-3.5 h-3.5" x-show="$store.voice.screenSharingUsers[p.user_id]" />
                                <span x-text="p.name"></span>
                            </span>
                            <span x-show="p.muted" class="absolute bottom-2 right-2 bg-red-600 rounded-full w-6 h-6 flex items-center justify-center"><x-icon name="mic-off" class="w-3.5 h-3.5" /></span>
                            <button x-show="$store.voice.videoStreams[p.user_id]"
                                    @click.stop="expandedUser = p.user_id"
                                    class="absolute top-2 right-2 bg-black/50 hover:bg-black/70 rounded w-7 h-7 flex items-center justify-center"
                                    title="Развернуть"><x-icon name="expand" class="w-3.5 h-3.5" /></button>
                            <template x-if="!p.is_me && ['disconnected', 'failed'].includes($store.voice.connectionState[p.user_id])">
                                <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                    <span class="text-xs text-amber-300 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        Восстановление связи…
                                    </span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Развёрнутый просмотр плитки/демонстрации экрана поверх всего интерфейса --}}
                <template x-teleport="body">
                    <div x-show="expandedUser !== null" x-cloak
                         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         @click.self="expandedUser = null"
                         class="fixed inset-0 bg-black/90 z-[60] flex items-center justify-center p-6">
                        <template x-if="expandedUser !== null">
                            <div class="relative w-full h-full flex items-center justify-center">
                                <template x-for="p in $store.voice.participants.filter(x => x.user_id === expandedUser)" :key="p.user_id">
                                    <div class="relative max-w-full max-h-full">
                                        <template x-if="$store.voice.videoStreams[p.user_id]">
                                            <video autoplay playsinline muted
                                                   class="max-w-full max-h-[85vh] rounded-lg"
                                                   :class="$store.voice.screenSharingUsers[p.user_id] ? 'object-contain' : 'object-cover'"
                                                   x-effect="$el.srcObject = $store.voice.videoStreams[p.user_id] || null"
                                                   x-ref="expandedVideo"></video>
                                        </template>
                                        <template x-if="!$store.voice.videoStreams[p.user_id]">
                                            <img :src="p.avatar_url" class="w-40 h-40 rounded-full mx-auto">
                                        </template>
                                        <span class="absolute bottom-3 left-3 text-sm bg-black/60 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                                            <x-icon name="monitor" class="w-3.5 h-3.5" x-show="$store.voice.screenSharingUsers[p.user_id]" />
                                            <span x-text="p.name"></span>
                                        </span>
                                    </div>
                                </template>
                                <button @click="expandedUser = null"
                                        class="absolute top-4 right-4 bg-black/50 hover:bg-black/70 rounded-full w-10 h-10 flex items-center justify-center"
                                        title="Закрыть"><x-icon name="x" class="w-5 h-5" /></button>
                                <button x-show="$refs.expandedVideo"
                                        @click="$refs.expandedVideo?.requestFullscreen?.()"
                                        class="absolute top-4 right-16 bg-black/50 hover:bg-black/70 rounded-full w-10 h-10 flex items-center justify-center"
                                        title="На весь экран"><x-icon name="maximize" class="w-4 h-4" /></button>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Панель управления снизу, как в référence --}}
                <div class="flex justify-center gap-3 py-4">
                    <button @click="$store.voice.toggleMute()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.muted ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Микрофон">
                        <template x-if="$store.voice.muted"><x-icon name="mic-off" class="w-5 h-5" /></template>
                        <template x-if="!$store.voice.muted"><x-icon name="mic" class="w-5 h-5" /></template>
                    </button>
                    <button @click="$store.voice.toggleDeafen()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.deafened ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Звук">
                        <template x-if="$store.voice.deafened"><x-icon name="bell-off" class="w-5 h-5" /></template>
                        <template x-if="!$store.voice.deafened"><x-icon name="bell" class="w-5 h-5" /></template>
                    </button>
                    <button @click="$store.voice.toggleCamera()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.cameraEnabled ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Камера">
                        <x-icon name="video" class="w-5 h-5" />
                    </button>
                    <button @click="$store.voice.toggleScreenShare()" class="btn-lift w-11 h-11 rounded-full flex items-center justify-center"
                            :class="$store.voice.screenSharing ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'"
                            title="Демонстрация экрана">
                        <x-icon name="monitor" class="w-5 h-5" />
                    </button>
                    <button x-data @click="$dispatch('open-voice-settings')" class="icon-gear btn-lift w-11 h-11 rounded-full flex items-center justify-center bg-[#3a3c42] hover:bg-[#43454b]" title="Настройки голоса">
                        <x-icon name="settings" class="w-5 h-5" />
                    </button>
                    <button @click="$store.voice.leave()" class="btn-lift w-14 h-11 rounded-full flex items-center justify-center bg-red-600 hover:bg-red-500" title="Покинуть канал">
                        <x-icon name="phone-off" class="w-5 h-5" />
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
