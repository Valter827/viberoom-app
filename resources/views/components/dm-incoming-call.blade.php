{{-- Входящий звонок в личных сообщениях.
     Работает независимо от $store.voice: опрашивает тот же "почтовый ящик" сигналов
     (см. VoiceController::pollSignals) в поисках сигналов типа 'ring'/'cancel',
     адресованных мне — эти сигналы core-обработчик звонка в app.js специально
     игнорирует (см. handleSignal), чтобы не мешать уже идущему P2P-соединению.

     Живёт на странице ЛС (dm/show.blade.php) и следит только за DM-каналами,
     которые видны в сайдбаре слева — то есть уведомление о звонке придёт, только
     пока вы находитесь на странице личных сообщений (см. ограничение в README-заметке
     ниже). Это сознательный компромисс без выделенного WebSocket-сервера уведомлений. --}}
<div
    x-data="dmIncomingCall({
        watchedChannelIds: {{ $dmChannels->pluck('id')->values()->toJson() }},
        myName: {{ Js::from(Auth::user()->name) }},
    })"
    x-init="init()"
>
    <template x-teleport="body">
        <div x-show="incoming" x-cloak
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed bottom-6 right-6 z-[90] w-80 bg-[#111214] rounded-2xl shadow-2xl p-4 border border-white/10">
            <template x-if="incoming">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img :src="incoming.caller_avatar" class="w-12 h-12 rounded-full">
                        <div class="min-w-0">
                            <p class="font-semibold truncate" x-text="incoming.caller_name"></p>
                            <p class="text-xs text-gray-400 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span x-text="incoming.video ? 'Видеозвонок…' : 'Звонок…'"></span>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="decline()" class="btn-lift flex-1 h-11 rounded-xl bg-red-600 hover:bg-red-500 flex items-center justify-center gap-2 text-sm font-medium">
                            <x-icon name="phone-off" class="w-4 h-4" /> Отклонить
                        </button>
                        <button @click="accept()" class="btn-lift flex-1 h-11 rounded-xl bg-emerald-600 hover:bg-emerald-500 flex items-center justify-center gap-2 text-sm font-medium">
                            <x-icon :name="'phone'" class="w-4 h-4" /> Ответить
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
