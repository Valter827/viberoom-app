{{-- Панель активного звонка внутри ЛС — в отличие от голосовых каналов сервера,
     звонок в личке не занимает всю область чата: можно одновременно говорить
     и переписываться, поэтому это компактная полоса сверху, а не отдельный экран. --}}
<div x-show="$store.voice.joined && $store.voice.channelId === {{ $activeChannel->id }}" x-cloak
     class="border-b border-black/20 bg-[#1a1b1e] flex-shrink-0">

    {{-- Компактная строка состояния звонка --}}
    <div class="h-11 flex items-center px-4 gap-3">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
        <span class="text-xs text-emerald-400 font-medium" x-text="$store.voice.participants.length > 1 ? 'Звонок' : 'Вызов…'"></span>
        <span class="text-xs text-gray-500" x-text="$store.voice.participants.length > 1 ? $store.voice.formattedDuration : ''"></span>

        <div class="flex-1"></div>

        <button @click="$store.voice.toggleMute()" class="icon-action" :class="$store.voice.muted ? 'text-red-400' : ''" title="Микрофон">
            <template x-if="$store.voice.muted"><x-icon name="mic-off" class="w-4 h-4" /></template>
            <template x-if="!$store.voice.muted"><x-icon name="mic" class="w-4 h-4" /></template>
        </button>
        <button @click="$store.voice.toggleCamera()" class="icon-action" :class="$store.voice.cameraEnabled ? 'text-emerald-400' : ''" title="Камера">
            <template x-if="$store.voice.cameraEnabled"><x-icon name="video" class="w-4 h-4" /></template>
            <template x-if="!$store.voice.cameraEnabled"><x-icon name="video-off" class="w-4 h-4" /></template>
        </button>
        <button @click="$store.voice.toggleScreenShare()" class="icon-action" :class="$store.voice.screenSharing ? 'text-emerald-400' : ''" title="Демонстрация экрана">
            <x-icon name="monitor" class="w-4 h-4" />
        </button>
        <button @click="$store.voice.leave()" class="icon-action text-red-400 hover:text-white hover:bg-red-500" title="Завершить звонок">
            <x-icon name="phone-off" class="w-4 h-4" />
        </button>
    </div>

    {{-- Видео-плитки — появляются только если у кого-то включена камера/демонстрация экрана,
         чтобы не занимать место в обычном аудиозвонке --}}
    <template x-if="Object.keys($store.voice.videoStreams).length > 0">
        <div class="grid gap-2 p-2 pt-0" :style="`grid-template-columns: repeat(${Math.min(Object.keys($store.voice.videoStreams).length, 2)}, minmax(160px, 280px))`">
            <template x-for="p in $store.voice.participants.filter(p => $store.voice.videoStreams[p.user_id])" :key="p.user_id">
                <div class="relative bg-black rounded-lg aspect-video overflow-hidden">
                    <video autoplay playsinline muted
                           class="w-full h-full"
                           :class="$store.voice.screenSharingUsers[p.user_id] ? 'object-contain' : 'object-cover'"
                           x-effect="$el.srcObject = $store.voice.videoStreams[p.user_id] || null"></video>
                    <span class="absolute bottom-1.5 left-1.5 text-[11px] bg-black/60 px-1.5 py-0.5 rounded" x-text="p.name"></span>
                </div>
            </template>
        </div>
    </template>
</div>
