import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Открывает всплывающую карточку профиля пользователя (как в Discord).
 * Вызывается из onclick/@click в шаблонах: openProfile(userId, event).
 * Сам попап слушает событие 'show-profile-popover' (см. components/profile-popover.blade.php).
 */
window.openProfile = function (userId, event) {
    if (event) {
        event.stopPropagation();
    }
    const rect = event?.currentTarget?.getBoundingClientRect();
    window.dispatchEvent(new CustomEvent('show-profile-popover', {
        detail: {
            userId,
            x: rect ? rect.right + 8 : 200,
            y: rect ? rect.top : 200,
        },
    }));
};

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

/**
 * Простые звуковые эффекты через Web Audio API — короткие синтезированные тона,
 * без внешних аудиофайлов. Можно выключить в настройках (localStorage).
 */
const Sounds = {
    ctx: null,

    enabled() {
        return localStorage.getItem('sound_effects_disabled') !== '1';
    },

    getCtx() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
        return this.ctx;
    },

    async tone(freq, duration = 0.12, delay = 0, volume = 0.15) {
        if (!this.enabled()) return;
        try {
            const ctx = this.getCtx();
            // Браузеры создают AudioContext в состоянии 'suspended' до первого
            // пользовательского жеста, а Chrome вдобавок может "усыпить" уже рабочий
            // контекст энергосбережением, если через него давно не проходил звук.
            // Без await здесь resume() не успевает завершиться до osc.start(), и звук
            // молча пропадает — без единой ошибки в консоли.
            if (ctx.state === 'suspended') {
                await ctx.resume();
            }
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.frequency.value = freq;
            osc.type = 'sine';
            gain.gain.setValueAtTime(volume, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + duration);
        } catch (e) { /* аудио недоступно — просто молчим */ }
    },

    voiceJoin() {
        this.tone(523, 0.09, 0);
        this.tone(784, 0.12, 0.08);
    },
    voiceLeave() {
        this.tone(600, 0.09, 0);
        this.tone(400, 0.14, 0.07);
    },
    messageReceived() {
        this.tone(880, 0.08, 0, 0.08);
    },
    mentionReceived() {
        this.tone(660, 0.08, 0, 0.12);
        this.tone(990, 0.1, 0.09, 0.12);
    },
};
window.Sounds = Sounds;

// "Разогреваем" AudioContext на первый же клик/нажатие клавиши где угодно на
// странице — иначе звук уведомления о новом сообщении (пришедшем без явного
// клика пользователя именно в этот момент) может не воспроизвестись.
['click', 'keydown'].forEach(evt => {
    document.addEventListener(evt, () => {
        const ctx = Sounds.getCtx();
        if (ctx.state === 'suspended') ctx.resume();
    }, { once: true });
});

/**
 * Глобальное состояние голосового звонка (Alpine.store), НЕ привязанное к
 * конкретной странице канала. Живёт на уровне всего приложения, поэтому
 * переход между текстовыми каналами (обычная навигация браузера) не рвёт
 * соединение мгновенно — при каждой загрузке страницы состояние восстанавливается
 * из sessionStorage и звонок автоматически переподключается в фоне (см. init()).
 * Из-за полной перезагрузки страницы это не идеальный "бесшовный" P2P (короткое
 * переподключение при каждом переходе неизбежно без полного перехода на SPA),
 * но пользователь не должен ощущать себя "выкинутым" — полоса звонка остаётся
 * видимой и активной на любой странице сервера.
 */
document.addEventListener('alpine:init', () => {
    /**
     * Общее состояние "какое контекстное меню сейчас открыто" (сервер/канал).
     * Правый клик по одному пункту должен закрывать меню, открытое у другого —
     * а обычный @click.outside этого не делает, потому что реагирует только
     * на 'click', а не на 'contextmenu'.
     */
    Alpine.store('contextMenu', {
        openId: null,
        renamingId: null,
        x: 0,
        y: 0,
        open(id, x, y) {
            this.openId = id;
            this.renamingId = null;
            this.x = x;
            this.y = y;
        },
        close(id = null) {
            if (id === null || this.openId === id) {
                this.openId = null;
            }
        },
        startRename(id) {
            this.openId = null;
            this.renamingId = id;
        },
        stopRename(id = null) {
            if (id === null || this.renamingId === id) {
                this.renamingId = null;
            }
        },
    });

    Alpine.store('voice', {
        channelId: null,
        channelName: '',
        myId: null,
        serverId: null,
        joined: false,
        connecting: false,
        muted: false,
        deafened: false,
        participants: [],
        speaking: {},
        peers: {},
        audioEls: {},
        localStream: null,
        // Видео: камера и демонстрация экрана используют один и тот же "видео слот"
        // в исходящем соединении — как в Discord, нельзя включить оба одновременно.
        cameraEnabled: false,
        screenSharing: false,
        localVideoStream: null,
        localScreenStream: null,
        videoStreams: {}, // userId -> MediaStream (моя камера/шеринг + входящие от собеседников)
        screenSharingUsers: {}, // userId -> true, если это именно демонстрация экрана (для рамки/подписи)
        lastSignalId: 0,
        heartbeatTimer: null,
        signalTimer: null,
        analysers: {},
        rafId: null,
        joinedAt: null,
        durationSeconds: 0,
        durationTimer: null,

        init() {
            const saved = sessionStorage.getItem('voice_session');
            if (saved) {
                try {
                    const { channelId, serverId, channelName } = JSON.parse(saved);
                    if (channelId) this.join(channelId, serverId, channelName, true);
                } catch (e) { /* ignore corrupt state */ }
            }
        },

        getMicConstraints() {
            const deviceId = localStorage.getItem('voice_input_device');
            const profile = localStorage.getItem('voice_input_profile') || 'standard'; // standard|studio
            const base = {
                echoCancellation: profile !== 'studio',
                noiseSuppression: profile !== 'studio',
                autoGainControl: profile !== 'studio',
            };
            return deviceId ? { ...base, deviceId: { exact: deviceId } } : base;
        },

        getCameraConstraints() {
            const deviceId = localStorage.getItem('video_camera_device');
            const base = { width: { ideal: 1280 }, height: { ideal: 720 } };
            return deviceId ? { ...base, deviceId: { exact: deviceId } } : base;
        },

        getScreenShareConstraints() {
            const heights = { '720p': 720, '1080p': 1080, '1440p': 1440 };
            const resolution = localStorage.getItem('screen_share_resolution') || '1080p';
            const fps = parseInt(localStorage.getItem('screen_share_fps') || '30');
            return {
                video: { height: { ideal: heights[resolution] || 1080 }, frameRate: { ideal: fps } },
                audio: localStorage.getItem('screen_share_audio') === '1',
            };
        },

        /**
         * Прогоняет "сырой" поток микрофона через GainNode, чтобы ползунок
         * "Громкость микрофона" реально влиял на передаваемый звук, а не
         * только на локальный тест. Возвращает поток с обработанным треком,
         * который и уходит в RTCPeerConnection.
         */
        buildOutgoingStream(rawStream) {
            this.micCtx = new (window.AudioContext || window.webkitAudioContext)();
            const source = this.micCtx.createMediaStreamSource(rawStream);
            this.micGainNode = this.micCtx.createGain();
            this.micGainNode.gain.value = (parseInt(localStorage.getItem('voice_mic_volume') || '100')) / 100;
            const destination = this.micCtx.createMediaStreamDestination();
            source.connect(this.micGainNode);
            this.micGainNode.connect(destination);
            return destination.stream;
        },

        setMicVolume(value) {
            localStorage.setItem('voice_mic_volume', value);
            if (this.micGainNode) this.micGainNode.gain.value = value / 100;
        },

        setOutputVolume(value) {
            localStorage.setItem('voice_output_volume', value);
            Object.values(this.audioEls).forEach(audio => { audio.volume = value / 100; });
        },

        async join(channelId, serverId, channelName, silent = false) {
            if (this.joined && this.channelId === channelId) return;
            if (this.joined) await this.leave();

            this.connecting = true;
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: this.getMicConstraints(), video: false });
                this.outgoingStream = this.buildOutgoingStream(this.localStream);
            } catch (e) {
                this.connecting = false;
                if (!silent) alert('Не удалось получить доступ к микрофону: ' + e.message);
                sessionStorage.removeItem('voice_session');
                return;
            }

            const res = await fetch(`/channels/${channelId}/voice/join`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            });
            if (!res.ok) {
                this.connecting = false;
                this.localStream.getTracks().forEach(t => t.stop());
                sessionStorage.removeItem('voice_session');
                return;
            }
            const data = await res.json();

            this.channelId = channelId;
            this.serverId = serverId;
            this.channelName = channelName;
            this.joined = true;
            this.connecting = false;
            this.lastSignalId = 0;
            sessionStorage.setItem('voice_session', JSON.stringify({ channelId, serverId, channelName }));
            window.dispatchEvent(new CustomEvent('voice-participants-changed'));
            if (!silent) Sounds.voiceJoin();

            this.myId = parseInt(document.querySelector('meta[name="current-user-id"]')?.content) || 'me';
            this.setupSpeakingDetection(this.localStream, this.myId);
            this.updateParticipants(data.participants);

            for (const p of data.participants) {
                if (!p.is_me) this.connectTo(p.user_id, true);
            }

            // таймер длительности звонка — joined_at приходит с сервера, чтобы
            // при восстановлении сессии (перезагрузка страницы) время не сбрасывалось
            this.joinedAt = data.joined_at ? new Date(data.joined_at) : new Date();
            this.durationSeconds = Math.floor((Date.now() - this.joinedAt.getTime()) / 1000);
            clearInterval(this.durationTimer);
            this.durationTimer = setInterval(() => {
                this.durationSeconds = Math.max(0, Math.floor((Date.now() - this.joinedAt.getTime()) / 1000));
            }, 1000);

            this.heartbeatTimer = setInterval(() => this.heartbeat(), 5000);
            this.signalTimer = setInterval(() => this.pollSignals(), 1500);

            // если в профиле включено "всегда включать камеру при входе" — включаем сразу
            if (!silent && localStorage.getItem('video_default_enabled') === '1') {
                this.toggleCamera();
            }
        },

        /**
         * Пересогласовывает уже установленное P2P-соединение после добавления/удаления
         * видео-дорожки (addTrack/removeTrack сами по себе не долетают до собеседника
         * без нового offer/answer).
         */
        async renegotiate(userId) {
            const pc = this.peers[userId];
            if (!pc || pc.signalingState !== 'stable') return; // согласование уже идёт — дождёмся его завершения
            try {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                this.sendSignal(userId, 'offer', JSON.stringify(offer));
            } catch (e) { /* ignore glare — редкий edge case для P2P-демо */ }
        },

        addVideoTrackToPeers(track) {
            for (const [userId, pc] of Object.entries(this.peers)) {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) {
                    sender.replaceTrack(track);
                } else {
                    pc.addTrack(track, this.localVideoStream || this.localScreenStream);
                    this.renegotiate(userId);
                }
            }
        },

        removeVideoTrackFromPeers() {
            for (const [userId, pc] of Object.entries(this.peers)) {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (sender) {
                    pc.removeTrack(sender);
                    this.renegotiate(userId);
                }
            }
        },

        async toggleCamera() {
            if (this.cameraEnabled) { this.stopCamera(); return; }
            if (this.screenSharing) await this.stopScreenShare();

            try {
                this.localVideoStream = await navigator.mediaDevices.getUserMedia({ video: this.getCameraConstraints() });
            } catch (e) {
                alert('Не удалось получить доступ к камере: ' + e.message);
                return;
            }
            this.cameraEnabled = true;
            this.videoStreams = { ...this.videoStreams, [this.myId]: this.localVideoStream };
            this.addVideoTrackToPeers(this.localVideoStream.getVideoTracks()[0]);
        },

        stopCamera() {
            this.cameraEnabled = false;
            if (this.localVideoStream) this.localVideoStream.getTracks().forEach(t => t.stop());
            this.localVideoStream = null;
            const vs = { ...this.videoStreams };
            delete vs[this.myId];
            this.videoStreams = vs;
            this.removeVideoTrackFromPeers();
        },

        async toggleScreenShare() {
            if (this.screenSharing) { await this.stopScreenShare(); return; }
            if (this.cameraEnabled) this.stopCamera();

            const constraints = this.getScreenShareConstraints();
            try {
                this.localScreenStream = await navigator.mediaDevices.getDisplayMedia(constraints);
            } catch (e) {
                return; // пользователь закрыл системный диалог выбора окна/экрана
            }
            this.screenSharing = true;
            this.videoStreams = { ...this.videoStreams, [this.myId]: this.localScreenStream };
            this.screenSharingUsers = { ...this.screenSharingUsers, [this.myId]: true };

            const videoTrack = this.localScreenStream.getVideoTracks()[0];
            videoTrack.onended = () => this.stopScreenShare(); // пользователь нажал "Остановить показ" в браузере
            this.addVideoTrackToPeers(videoTrack);

            if (constraints.audio) {
                this.localScreenStream.getAudioTracks().forEach(track => {
                    for (const [userId, pc] of Object.entries(this.peers)) {
                        pc.addTrack(track, this.localScreenStream);
                        this.renegotiate(userId);
                    }
                });
            }
        },

        async stopScreenShare() {
            this.screenSharing = false;
            if (this.localScreenStream) this.localScreenStream.getTracks().forEach(t => t.stop());
            this.localScreenStream = null;
            const vs = { ...this.videoStreams };
            delete vs[this.myId];
            this.videoStreams = vs;
            const ss = { ...this.screenSharingUsers };
            delete ss[this.myId];
            this.screenSharingUsers = ss;
            this.removeVideoTrackFromPeers();
        },

        get formattedDuration() {
            const total = this.durationSeconds;
            const h = Math.floor(total / 3600);
            const m = Math.floor((total % 3600) / 60);
            const s = total % 60;
            const pad = (n) => String(n).padStart(2, '0');
            return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
        },

        updateParticipants(list) {
            const before = new Set(this.participants.map(p => p.user_id));
            this.participants = list;
            for (const p of list) {
                if (!p.is_me && !before.has(p.user_id) && !this.peers[p.user_id]) {
                    this.connectTo(p.user_id, true);
                }
            }
        },

        async heartbeat() {
            if (!this.channelId) return;
            const res = await fetch(`/channels/${this.channelId}/voice/heartbeat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ muted: this.muted }),
            });
            if (res.ok) {
                const data = await res.json();
                this.updateParticipants(data.participants);
                const stillHere = new Set(data.participants.map(p => p.user_id));
                for (const uid of Object.keys(this.peers)) {
                    if (!stillHere.has(parseInt(uid))) this.disconnectFrom(parseInt(uid));
                }
            }
        },

        connectTo(userId, isInitiator) {
            if (this.peers[userId]) return;

            const pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
            // "Вежливая" сторона при коллизии офферов (оба одновременно пере-согласовывают
            // соединение, например включили камеру в один момент) уступает и откатывает
            // свой оффер — иначе один из offer/answer обменов падает с "wrong state".
            pc.isPolite = !isInitiator;
            this.peers[userId] = pc;

            const streamToSend = this.outgoingStream || this.localStream;
            streamToSend.getTracks().forEach(track => pc.addTrack(track, streamToSend));

            // если камера или демонстрация экрана уже включены — сразу отдаём видео-дорожку
            // новому участнику вместе с первым offer, без отдельной renegotiation
            const myVideoStream = this.localVideoStream || this.localScreenStream;
            if (myVideoStream) {
                myVideoStream.getVideoTracks().forEach(track => pc.addTrack(track, myVideoStream));
            }

            pc.onicecandidate = (e) => {
                if (e.candidate) this.sendSignal(userId, 'candidate', JSON.stringify(e.candidate));
            };

            pc.ontrack = (e) => {
                if (e.track.kind === 'video') {
                    this.videoStreams = { ...this.videoStreams, [userId]: e.streams[0] };
                    e.track.onended = () => {
                        const vs = { ...this.videoStreams };
                        delete vs[userId];
                        this.videoStreams = vs;
                    };
                    return;
                }

                let audio = this.audioEls[userId];
                if (!audio) {
                    audio = document.createElement('audio');
                    audio.autoplay = true;
                    audio.muted = this.deafened;
                    audio.volume = (parseInt(localStorage.getItem('voice_output_volume') || '100')) / 100;
                    const outputDevice = localStorage.getItem('voice_output_device');
                    if (outputDevice && audio.setSinkId) {
                        audio.setSinkId(outputDevice).catch(() => {});
                    }
                    document.body.appendChild(audio);
                    this.audioEls[userId] = audio;
                }
                audio.srcObject = e.streams[0];
                this.setupSpeakingDetection(e.streams[0], userId);
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
            if (this.audioEls[userId]) {
                this.audioEls[userId].remove();
                delete this.audioEls[userId];
            }
            if (this.analysers[userId]) {
                delete this.analysers[userId];
            }
            delete this.speaking[userId];
            if (this.videoStreams[userId]) {
                const vs = { ...this.videoStreams };
                delete vs[userId];
                this.videoStreams = vs;
            }
            if (this.screenSharingUsers[userId]) {
                const ss = { ...this.screenSharingUsers };
                delete ss[userId];
                this.screenSharingUsers = ss;
            }
        },

        async sendSignal(toUserId, type, payload) {
            await fetch(`/channels/${this.channelId}/voice/signal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ to_user_id: toUserId, type, payload }),
            });
        },

        async pollSignals() {
            if (!this.channelId) return;
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
            if (sig.type === 'leave') { this.disconnectFrom(fromId); return; }

            if (!this.peers[fromId]) this.connectTo(fromId, false);
            const pc = this.peers[fromId];
            const payload = JSON.parse(sig.payload);

            if (sig.type === 'offer') {
                // Коллизия: у нас у самих уже есть свой неподтверждённый оффер (например,
                // мы сами только что включили камеру одновременно с собеседником).
                const collision = pc.signalingState !== 'stable';
                if (collision && !pc.isPolite) {
                    return; // "невежливая" сторона игнорирует чужой оффер, дожидается своего answer
                }
                if (collision) {
                    await pc.setLocalDescription({ type: 'rollback' }); // "вежливая" сторона уступает
                }
                await pc.setRemoteDescription(new RTCSessionDescription(payload));
                const answer = await pc.createAnswer();
                await pc.setLocalDescription(answer);
                this.sendSignal(fromId, 'answer', JSON.stringify(answer));
            } else if (sig.type === 'answer') {
                // Дублирующийся/устаревший answer (например, из-за повторного опроса
                // сигналов) вне состояния "есть свой неподтверждённый оффер" — просто игнорируем,
                // иначе setRemoteDescription падает с "Called in wrong state: stable".
                if (pc.signalingState === 'have-local-offer') {
                    await pc.setRemoteDescription(new RTCSessionDescription(payload));
                }
            } else if (sig.type === 'candidate') {
                try { await pc.addIceCandidate(new RTCIceCandidate(payload)); } catch (e) { /* ignore */ }
            }
        },

        toggleMute() {
            this.muted = !this.muted;
            if (this.localStream) this.localStream.getAudioTracks().forEach(t => t.enabled = !this.muted);
        },

        toggleDeafen() {
            this.deafened = !this.deafened;
            if (this.deafened && !this.muted) this.toggleMute();
            Object.values(this.audioEls).forEach(a => a.muted = this.deafened);
        },

        setupSpeakingDetection(stream, key) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const source = ctx.createMediaStreamSource(stream);
                const analyser = ctx.createAnalyser();
                analyser.fftSize = 512;
                source.connect(analyser);
                this.analysers[key] = { analyser, data: new Uint8Array(analyser.frequencyBinCount), ctx };
                if (!this.rafId) this.tickSpeaking();
            } catch (e) { /* getUserMedia/AudioContext unsupported edge case — skip silently */ }
        },

        tickSpeaking() {
            for (const [key, a] of Object.entries(this.analysers)) {
                a.analyser.getByteFrequencyData(a.data);
                const avg = a.data.reduce((s, v) => s + v, 0) / a.data.length;
                const threshold = parseInt(localStorage.getItem('voice_sensitivity') || '12');
                const isSpeaking = avg > threshold && !(String(key) === String(this.myId) && this.muted);
                this.speaking[key] = isSpeaking;
            }
            this.rafId = requestAnimationFrame(() => this.tickSpeaking());
        },

        async leave() {
            clearInterval(this.heartbeatTimer);
            clearInterval(this.signalTimer);
            clearInterval(this.durationTimer);
            this.joinedAt = null;
            this.durationSeconds = 0;
            if (this.rafId) cancelAnimationFrame(this.rafId);
            this.rafId = null;

            for (const uid of Object.keys(this.peers)) this.disconnectFrom(parseInt(uid));
            Object.values(this.analysers).forEach(a => { try { a.ctx.close(); } catch (e) {} });
            this.analysers = {};
            this.speaking = {};

            if (this.localStream) this.localStream.getTracks().forEach(t => t.stop());
            if (this.localVideoStream) this.localVideoStream.getTracks().forEach(t => t.stop());
            if (this.localScreenStream) this.localScreenStream.getTracks().forEach(t => t.stop());
            this.localVideoStream = null;
            this.localScreenStream = null;
            this.cameraEnabled = false;
            this.screenSharing = false;
            this.videoStreams = {};
            this.screenSharingUsers = {};
            if (this.micCtx) { try { this.micCtx.close(); } catch (e) {} }
            this.micCtx = null;
            this.micGainNode = null;
            this.outgoingStream = null;

            const channelId = this.channelId;
            this.joined = false;
            this.channelId = null;
            this.participants = [];
            sessionStorage.removeItem('voice_session');

            if (channelId) {
                Sounds.voiceLeave();
                await fetch(`/channels/${channelId}/voice/leave`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    keepalive: true,
                });
            }
            window.dispatchEvent(new CustomEvent('voice-participants-changed'));
        },
    });

    Alpine.store('voice').init();
});

Alpine.start();
