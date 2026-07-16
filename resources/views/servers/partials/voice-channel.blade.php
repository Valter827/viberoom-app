{{-- Голосовой канал: presence-список + WebRTC P2P звонок с сигналингом через polling --}}
<div
    x-data="voiceChannel({ channelId: {{ $activeChannel->id }}, myId: {{ Auth::id() }}, myName: {{ Js::from(Auth::user()->name) }}, myAvatar: {{ Js::from(Auth::user()->avatar_url) }} })"
    x-init="init()"
    class="flex-1 flex flex-col bg-[#313338]"
>
    <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
        <span class="text-gray-400 mr-1.5">🔊</span>
        <h2 class="font-semibold">{{ $activeChannel->name }}</h2>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center gap-6 p-6">
        <template x-if="!joined">
            <button @click="join()" class="bg-[#5865F2] hover:bg-[#4752c4] px-6 py-3 rounded-lg font-medium">
                🔊 Присоединиться к голосовому каналу
            </button>
        </template>

        <template x-if="joined">
            <div class="w-full max-w-2xl">
                <div class="flex flex-wrap justify-center gap-6 mb-8">
                    <template x-for="p in participants" :key="p.user_id">
                        <div class="flex flex-col items-center gap-2">
                            <div class="relative">
                                <img :src="p.avatar_url" class="w-20 h-20 rounded-full border-4"
                                     :class="speaking[p.user_id] ? 'border-emerald-500' : 'border-transparent'">
                                <span x-show="p.muted" class="absolute bottom-0 right-0 bg-red-600 rounded-full w-7 h-7 flex items-center justify-center text-xs">🔇</span>
                            </div>
                            <span class="text-sm text-gray-300" x-text="p.name"></span>
                        </div>
                    </template>
                </div>

                <div class="flex justify-center gap-3">
                    <button @click="toggleMute()" class="px-5 py-3 rounded-full"
                            :class="muted ? 'bg-red-600 hover:bg-red-500' : 'bg-[#3a3c42] hover:bg-[#43454b]'">
                        <span x-text="muted ? '🔇 Микрофон выкл.' : '🎙️ Микрофон вкл.'"></span>
                    </button>
                    <button @click="leave()" class="px-5 py-3 rounded-full bg-red-600 hover:bg-red-500">
                        📞 Покинуть канал
                    </button>
                </div>

                <p class="text-xs text-gray-500 text-center mt-6 max-w-md mx-auto">
                    Звонок работает напрямую между браузерами (P2P). Если кто-то за корпоративным
                    файрволом/жёстким NAT, соединение может не установиться — для надёжной работы
                    во всех сетях в будущем можно добавить TURN-сервер.
                </p>
            </div>
        </template>
    </div>

    {{-- Скрытый аудио-элемент на каждого собеседника создаётся динамически в JS --}}
    <div id="voice-audio-container" class="hidden"></div>
</div>

<script>
    function voiceChannel({ channelId, myId, myName, myAvatar }) {
        return {
            channelId, myId, myName, myAvatar,
            joined: false,
            muted: false,
            participants: [],
            speaking: {},
            localStream: null,
            peers: {}, // user_id -> RTCPeerConnection
            lastSignalId: 0,
            heartbeatTimer: null,
            signalTimer: null,

            async init() {
                window.addEventListener('beforeunload', () => this.leave());
            },

            async join() {
                try {
                    this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                } catch (e) {
                    alert('Не удалось получить доступ к микрофону: ' + e.message);
                    return;
                }

                const res = await fetch(`/channels/${this.channelId}/voice/join`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                if (!res.ok) { alert('Не удалось зайти в голосовой канал'); return; }
                const data = await res.json();
                this.joined = true;
                this.updateParticipants(data.participants);

                // соединяемся со всеми, кто уже в канале
                for (const p of data.participants) {
                    if (p.user_id !== this.myId) this.connectTo(p.user_id, true);
                }

                this.heartbeatTimer = setInterval(() => this.heartbeat(), 5000);
                this.signalTimer = setInterval(() => this.pollSignals(), 1500);
            },

            updateParticipants(list) {
                const before = new Set(this.participants.map(p => p.user_id));
                this.participants = list;
                // если появился новый участник (кроме нас) — инициируем звонок к нему
                for (const p of list) {
                    if (p.user_id !== this.myId && !before.has(p.user_id) && !this.peers[p.user_id]) {
                        this.connectTo(p.user_id, true);
                    }
                }
            },

            async heartbeat() {
                const res = await fetch(`/channels/${this.channelId}/voice/heartbeat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ muted: this.muted }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.updateParticipants(data.participants);
                    // убираем звонок с теми, кто пропал
                    const stillHere = new Set(data.participants.map(p => p.user_id));
                    for (const uid of Object.keys(this.peers)) {
                        if (!stillHere.has(parseInt(uid))) this.disconnectFrom(parseInt(uid));
                    }
                }
            },

            connectTo(userId, isInitiator) {
                if (this.peers[userId]) return;

                const pc = new RTCPeerConnection({
                    iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
                });
                this.peers[userId] = pc;

                this.localStream.getTracks().forEach(track => pc.addTrack(track, this.localStream));

                pc.onicecandidate = (e) => {
                    if (e.candidate) {
                        this.sendSignal(userId, 'candidate', JSON.stringify(e.candidate));
                    }
                };

                pc.ontrack = (e) => {
                    let audio = document.getElementById('voice-audio-' + userId);
                    if (!audio) {
                        audio = document.createElement('audio');
                        audio.id = 'voice-audio-' + userId;
                        audio.autoplay = true;
                        document.getElementById('voice-audio-container').appendChild(audio);
                    }
                    audio.srcObject = e.streams[0];
                };

                if (isInitiator) {
                    pc.createOffer().then(offer => {
                        pc.setLocalDescription(offer);
                        this.sendSignal(userId, 'offer', JSON.stringify(offer));
                    });
                }
            },

            disconnectFrom(userId) {
                if (this.peers[userId]) {
                    this.peers[userId].close();
                    delete this.peers[userId];
                }
                const audio = document.getElementById('voice-audio-' + userId);
                if (audio) audio.remove();
            },

            async sendSignal(toUserId, type, payload) {
                await fetch(`/channels/${this.channelId}/voice/signal`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ to_user_id: toUserId, type, payload }),
                });
            },

            async pollSignals() {
                const res = await fetch(`/channels/${this.channelId}/voice/signals?after=${this.lastSignalId}`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const signals = await res.json();
                for (const sig of signals) {
                    this.lastSignalId = Math.max(this.lastSignalId, sig.id);
                    await this.handleSignal(sig);
                }
            },

            async handleSignal(sig) {
                const fromId = sig.from_user_id;

                if (sig.type === 'leave') {
                    this.disconnectFrom(fromId);
                    return;
                }

                if (!this.peers[fromId]) this.connectTo(fromId, false);
                const pc = this.peers[fromId];
                const payload = JSON.parse(sig.payload);

                if (sig.type === 'offer') {
                    await pc.setRemoteDescription(new RTCSessionDescription(payload));
                    const answer = await pc.createAnswer();
                    await pc.setLocalDescription(answer);
                    this.sendSignal(fromId, 'answer', JSON.stringify(answer));
                } else if (sig.type === 'answer') {
                    await pc.setRemoteDescription(new RTCSessionDescription(payload));
                } else if (sig.type === 'candidate') {
                    try { await pc.addIceCandidate(new RTCIceCandidate(payload)); } catch (e) {}
                }
            },

            toggleMute() {
                this.muted = !this.muted;
                if (this.localStream) {
                    this.localStream.getAudioTracks().forEach(t => t.enabled = !this.muted);
                }
            },

            async leave() {
                clearInterval(this.heartbeatTimer);
                clearInterval(this.signalTimer);
                for (const uid of Object.keys(this.peers)) this.disconnectFrom(parseInt(uid));
                if (this.localStream) this.localStream.getTracks().forEach(t => t.stop());
                this.joined = false;
                this.participants = [];

                await fetch(`/channels/${this.channelId}/voice/leave`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    keepalive: true,
                });
            },
        };
    }
</script>
