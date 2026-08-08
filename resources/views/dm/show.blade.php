@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden">
    @include('servers.partials.server-sidebar')

    {{-- Вторая колонка: список личных чатов --}}
    <aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
            <h1 class="font-semibold truncate">Личные сообщения</h1>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-3">
            <a href="{{ route('friends.index') }}"
               class="block px-2 py-2 rounded hover:bg-[#35373c] text-sm font-medium flex items-center gap-2 mb-3 text-gray-300 hover:text-white transition-colors">
                <x-icon name="users" class="w-4 h-4" /> Друзья
            </a>

            @if ($dmChannels->isNotEmpty())
                <h3 class="px-2 text-xs font-semibold uppercase text-gray-400 mb-1">Чаты</h3>
                <div class="space-y-0.5">
                    @foreach ($dmChannels as $dm)
                        @php $dmCompanion = $dm->otherParticipant(Auth::id()); @endphp
                        @if ($dmCompanion)
                            <a href="{{ route('dm.show', $dm) }}"
                               class="flex items-center gap-2 px-2 py-1.5 rounded text-sm transition-colors
                                      {{ $dm->id === $activeChannel->id ? 'bg-[#404249] text-white' : 'hover:bg-[#35373c] text-gray-300 hover:text-white' }}">
                                <div class="relative shrink-0">
                                    <img src="{{ $dmCompanion->avatar_url }}" class="w-7 h-7 rounded-full">
                                    <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full border-2 border-[#2B2D31] {{ $dmCompanion->isOnline() ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                                </div>
                                <span class="truncate">{{ $dmCompanion->name }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

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
            <button x-data @click="$dispatch('open-profile-settings')" class="icon-action icon-gear ml-auto" title="Настройки профиля"><x-icon name="settings" class="w-4 h-4" /></button>
        </div>
    </aside>

    {{-- Лента сообщений — тот же компонент, что и в каналах серверов --}}
    @include('servers.partials.chat-area')
</div>

@include('components.profile-popover')
@include('components.profile-settings-modal')
@include('components.dm-incoming-call')

<script>
    function csrfHeader() {
        return { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' };
    }

    // Кнопки "позвонить"/"видеозвонок" в шапке ЛС: шлём ring-сигнал собеседнику
    // и сами сразу заходим в звонок (переиспользуя тот же Alpine.store('voice'), что и
    // голосовые каналы серверов — с точки зрения WebRTC-сигналинга это просто
    // ещё один channelId).
    function dmCallButtons({ channelId, companionName }) {
        return {
            channelId,
            companionName,
            calling: false,
            ringTimeout: null,

            async call(video) {
                if (this.calling || (Alpine.store('voice').joined && Alpine.store('voice').channelId === this.channelId)) return;
                this.calling = true;
                try {
                    await fetch(`/channels/${this.channelId}/voice/ring`, {
                        method: 'POST',
                        headers: { ...csrfHeader(), 'Content-Type': 'application/json' },
                        body: JSON.stringify({ video: !!video }),
                    });
                    await Alpine.store('voice').join(this.channelId, null, this.companionName);
                    if (video) await Alpine.store('voice').toggleCamera();

                    // Если за 45 секунд никто не поднял трубку — считаем звонок несостоявшимся
                    clearTimeout(this.ringTimeout);
                    this.ringTimeout = setTimeout(() => {
                        if (Alpine.store('voice').joined && Alpine.store('voice').channelId === this.channelId && Alpine.store('voice').participants.length < 2) {
                            fetch(`/channels/${this.channelId}/voice/cancel-ring`, { method: 'POST', headers: csrfHeader() });
                            Alpine.store('voice').leave();
                            window.dispatchEvent(new CustomEvent('notify', { detail: `${this.companionName} не ответил(а)` }));
                        }
                    }, 45000);
                } finally {
                    this.calling = false;
                }
            },
        };
    }

    // Всплывающее окно "входящий звонок": опрашивает тот же канал сигналов, что и
    // голосовые звонки, но независимо от Alpine.store('voice') — чтобы уведомление приходило,
    // даже если вы ещё не зашли в разговор (или уже говорите в другом канале).
    function dmIncomingCall({ watchedChannelIds, myName }) {
        return {
            watchedChannelIds,
            incoming: null, // { channelId, caller_name, caller_avatar, video }
            cursors: {},
            pollTimer: null,

            init() {
                this.watchedChannelIds.forEach(id => { this.cursors[id] = 0; });
                this.poll();
                this.pollTimer = setInterval(() => this.poll(), 2500);
                window.addEventListener('beforeunload', () => clearInterval(this.pollTimer));
            },

            async poll() {
                for (const channelId of this.watchedChannelIds) {
                    // Если уже разговариваем в этом канале — новые ring нам не интересны.
                    if (Alpine.store('voice').joined && Alpine.store('voice').channelId === channelId) continue;
                    try {
                        const res = await fetch(`/channels/${channelId}/voice/signals?after=${this.cursors[channelId]}`, { headers: { Accept: 'application/json' } });
                        if (!res.ok) continue;
                        const signals = await res.json();
                        for (const sig of signals) {
                            this.cursors[channelId] = Math.max(this.cursors[channelId], sig.id);
                            if (sig.type === 'ring') {
                                const payload = JSON.parse(sig.payload);
                                this.incoming = { channelId, ...payload };
                            } else if (sig.type === 'cancel' && this.incoming?.channelId === channelId) {
                                this.incoming = null;
                            }
                        }
                    } catch (e) { /* сеть моргнула — подождём следующего тика */ }
                }
            },

            async accept() {
                if (!this.incoming) return;
                const { channelId, video } = this.incoming;
                this.incoming = null;
                await Alpine.store('voice').join(channelId, null, myName);
                if (video) await Alpine.store('voice').toggleCamera();
                // Если звонок пришёл для чата, который сейчас не открыт — ничего страшного,
                // аудио/видео уже пошло через $store.voice, открывать этот чат необязательно.
            },

            decline() {
                if (!this.incoming) return;
                const { channelId } = this.incoming;
                fetch(`/channels/${channelId}/voice/cancel-ring`, { method: 'POST', headers: csrfHeader() });
                this.incoming = null;
            },
        };
    }
</script>
@endsection
