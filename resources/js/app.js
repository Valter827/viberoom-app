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
    // --- Голосовая панель: мьют/оглушение/камера/демонстрация экрана ---
    micMuteOn() {
        this.tone(320, 0.07, 0, 0.12);
    },
    micMuteOff() {
        this.tone(560, 0.07, 0, 0.12);
    },
    deafenOn() {
        this.tone(260, 0.09, 0, 0.13);
        this.tone(180, 0.12, 0.06, 0.11);
    },
    deafenOff() {
        this.tone(460, 0.07, 0, 0.1);
        this.tone(700, 0.09, 0.06, 0.1);
    },
    cameraOn() {
        this.tone(700, 0.08, 0, 0.1);
    },
    cameraOff() {
        this.tone(380, 0.08, 0, 0.1);
    },
    screenShareOn() {
        this.tone(760, 0.07, 0, 0.1);
        this.tone(1000, 0.09, 0.06, 0.1);
    },
    screenShareOff() {
        this.tone(500, 0.08, 0, 0.09);
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
     * Общее состояние — только КЛЮЧ элемента (канал/сервер), чьё меню сейчас
     * активно. Координаты, факт открытия и режим переименования каждый элемент
     * хранит у себя локально (в своём x-data), чтобы разные меню не могли
     * "склеиться" друг с другом или получить чужие координаты.
     *
     * Правый клик по одному пункту должен закрывать меню, открытое у другого —
     * а обычный @click.outside этого не делает, потому что реагирует только
     * на 'click', а не на 'contextmenu'. Поэтому каждый элемент подписывается
     * на activeKey через $watch и сам закрывается, если активен не он.
     */
    Alpine.store('contextMenu', {
        activeKey: null,
        activate(key) {
            this.activeKey = key;
        },
        deactivate(key) {
            if (this.activeKey === key) {
                this.activeKey = null;
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
        connectionState: {}, // userId -> 'connecting'|'connected'|'disconnected'|'failed' (для индикатора в UI)
        audioEls: {},
        localStream: null,
        turnConfig: null, // кэш iceServers на сессию звонка, см. loadTurnConfig()
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

        // Переводим технические ошибки getUserMedia в понятные подсказки —
        // "Permission denied" ничего не говорит обычному пользователю о том, что делать дальше.
        describeMicError(e) {
            if (location.protocol !== 'https:' && !['localhost', '127.0.0.1'].includes(location.hostname)) {
                return 'Браузер блокирует доступ к микрофону, потому что сайт открыт не по HTTPS. Голосовые каналы работают только на защищённом соединении (https://).';
            }
            switch (e.name) {
                case 'NotAllowedError':
                case 'PermissionDeniedError':
                    return 'Доступ к микрофону заблокирован. Разрешите использование микрофона для этого сайта в настройках браузера (значок замка/микрофона в адресной строке) и попробуйте снова.';
                case 'NotFoundError':
                case 'DevicesNotFoundError':
                    return 'Микрофон не найден. Проверьте, что устройство подключено и не занято другим приложением, затем попробуйте снова.';
                case 'NotReadableError':
                case 'TrackStartError':
                    return 'Не удалось получить доступ к микрофону — возможно, он уже используется другим приложением (Zoom, OBS и т.п.). Закройте его и попробуйте снова.';
                case 'OverconstrainedError':
                    return 'Выбранный микрофон недоступен с текущими настройками. Проверьте выбранное устройство в настройках голоса.';
                default:
                    return 'Не удалось получить доступ к микрофону: ' + (e.message || e.name || 'неизвестная ошибка');
            }
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
         *
         * ВАЖНО: AudioContext создаётся в состоянии 'suspended' в части браузеров
         * (особенно если между кликом "Присоединиться" и созданием контекста прошло
         * время на await getUserMedia()/разрешение доступа к микрофону). Пока контекст
         * не "разбужен" через resume(), граф узлов не обрабатывает звук — исходящий
         * MediaStreamDestination молча отдаёт тишину, хотя permission выдан и трек
         * физически существует. Это и была причина "микрофон не работает": собеседник
         * подключался, видел участника в звонке, но не слышал ни звука.
         */
        async buildOutgoingStream(rawStream) {
            try {
                this.micCtx = new (window.AudioContext || window.webkitAudioContext)();
                const source = this.micCtx.createMediaStreamSource(rawStream);
                this.micGainNode = this.micCtx.createGain();
                this.micGainNode.gain.value = (parseInt(localStorage.getItem('voice_mic_volume') || '100')) / 100;
                const destination = this.micCtx.createMediaStreamDestination();
                source.connect(this.micGainNode);
                this.micGainNode.connect(destination);

                if (this.micCtx.state === 'suspended') {
                    await this.micCtx.resume();
                }

                return destination.stream;
            } catch (e) {
                // Web Audio недоступен/упал — отдаём сырой поток напрямую, чтобы звонок
                // всё равно работал (просто без ползунка громкости микрофона).
                if (this.micCtx) { try { this.micCtx.close(); } catch (_) {} }
                this.micCtx = null;
                this.micGainNode = null;
                return rawStream;
            }
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
            // Креды для своего TURN запрашиваем сразу и параллельно с getUserMedia —
            // к моменту первого connectTo() они уже будут готовы, задержки не добавляем.
            const turnConfigPromise = this.loadTurnConfig();
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ audio: this.getMicConstraints(), video: false });
                this.outgoingStream = await this.buildOutgoingStream(this.localStream);
            } catch (e) {
                this.connecting = false;
                if (!silent) alert(this.describeMicError(e));
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

            await turnConfigPromise;
            for (const p of data.participants) {
                // Инициатор определяется детерминированно (у кого ID меньше), а не "все
                // считают себя инициатором" — иначе при коллизии офферов обе стороны
                // одновременно "невежливые" и не уступают друг другу, согласование
                // зависает и уходит в бесконечный цикл пересозданий соединения.
                if (!p.is_me) this.connectTo(p.user_id, this.myId < p.user_id);
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
            // 1.5с было заметной задержкой при установке звонка и восстановлении связи
            // (ICE-кандидаты и offer/answer идут через БД, а не напрямую) — 500ms ощутимо
            // ускоряет и то, и другое, нагрузка на БД при этом копеечная (лёгкий SELECT).
            this.signalTimer = setInterval(() => this.pollSignals(), 500);

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
                offer.sdp = this.tuneAudioSdp(offer.sdp);
                await pc.setLocalDescription(offer);
                this.sendSignal(userId, 'offer', JSON.stringify(offer));
            } catch (e) { /* ignore glare — редкий edge case для P2P-демо */ }
        },

        // Поднимает битрейт Opus с дефолтных ~32kbps до 64kbps (заметно чище звук на
        // "живом" голосе), включает inband FEC — восстановление одиночного потерянного
        // пакета без заикания в звуке, критично на мобильных сетях — и DTX, который не
        // тратит битрейт на паузы в речи (экономия трафика без потери качества, бесплатно).
        tuneAudioSdp(sdp) {
            const rtpmap = sdp.match(/a=rtpmap:(\d+) opus\/48000/i);
            if (!rtpmap) return sdp;
            const pt = rtpmap[1];
            const desired = { stereo: '0', useinbandfec: '1', usedtx: '1', maxaveragebitrate: '64000' };
            const fmtpRegex = new RegExp(`a=fmtp:${pt} ([^\r\n]*)`);
            const fmtpMatch = sdp.match(fmtpRegex);
            if (fmtpMatch) {
                const params = Object.fromEntries(fmtpMatch[1].split(';').filter(Boolean).map(kv => kv.split('=')));
                Object.assign(params, desired);
                const newLine = `a=fmtp:${pt} ` + Object.entries(params).map(([k, v]) => `${k}=${v}`).join(';');
                return sdp.replace(fmtpRegex, newLine);
            }
            const rtpmapLine = new RegExp(`(a=rtpmap:${pt} opus/48000/2\r\n)`);
            const newFmtp = `a=fmtp:${pt} ` + Object.entries(desired).map(([k, v]) => `${k}=${v}`).join(';') + '\r\n';
            return sdp.replace(rtpmapLine, `$1${newFmtp}`);
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
                this.tuneVideoSender(pc, this.screenSharing);
            }
        },

        // Голос не должен "тормозить" из-за камеры/демонстрации экрана на том же P2P-канале:
        // явно поднимаем битрейт и приоритет аудио-дорожки (по умолчанию браузер шлёт голос
        // всего ~32 kbps — этого достаточно "лишь бы работало", но на слух это как через
        // рацию). Часть полей (priority/networkPriority) — Chrome/Edge-специфичные хинты
        // планировщика полосы; там, где их нет, просто ничего не произойдёт.
        async tuneAudioSender(pc) {
            try {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'audio');
                if (!sender) return;
                const params = sender.getParameters();
                if (!params.encodings || !params.encodings.length) params.encodings = [{}];
                params.encodings[0].maxBitrate = 64000;
                params.encodings[0].priority = 'high';
                params.encodings[0].networkPriority = 'high';
                await sender.setParameters(params);
            } catch (e) { /* браузер не поддерживает эти поля setParameters — просто пропускаем */ }
        },

        // Ограничиваем битрейт видео сверху, чтобы на слабых аплинках (мобильный интернет,
        // домашний Wi-Fi с малой отдачей) картинка не съедала весь канал и не душила голос.
        async tuneVideoSender(pc, isScreenShare) {
            try {
                const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                if (!sender) return;
                const params = sender.getParameters();
                if (!params.encodings || !params.encodings.length) params.encodings = [{}];
                params.encodings[0].maxBitrate = isScreenShare ? 2_500_000 : 1_000_000;
                params.encodings[0].priority = 'low';
                await sender.setParameters(params);
            } catch (e) { /* ignore */ }
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
            if (this.cameraEnabled) { this.stopCamera(); Sounds.cameraOff(); return; }
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
            Sounds.cameraOn();
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
            if (this.screenSharing) { await this.stopScreenShare(); Sounds.screenShareOff(); return; }
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
            Sounds.screenShareOn();
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
                    this.connectTo(p.user_id, this.myId < p.user_id);
                }
            }
        },

        async heartbeat() {
            if (!this.channelId) return;
            const channelId = this.channelId; // фиксируем — за время await мог смениться/обнулиться
            const res = await fetch(`/channels/${channelId}/voice/heartbeat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ muted: this.muted }),
            });
            // Пока ждали ответ — вышли из канала или переключились на другой: этот
            // ответ уже неактуален, обрабатывать его (и тем более коннектиться к
            // кому-то с обнулённым/чужим channelId) нельзя.
            if (!this.joined || this.channelId !== channelId) return;
            if (res.ok) {
                const data = await res.json();
                if (!this.joined || this.channelId !== channelId) return;
                this.updateParticipants(data.participants);
                const stillHere = new Set(data.participants.map(p => p.user_id));
                for (const uid of Object.keys(this.peers)) {
                    if (!stillHere.has(parseInt(uid))) this.disconnectFrom(parseInt(uid));
                }
            }
        },

        // Свой coturn вместо публичного бесплатного TURN (openrelay.metered.ca):
        // у бесплатного общего сервера нет гарантий аптайма и есть общий лимит трафика
        // на всех его пользователей сразу — под нагрузкой это давало именно то, что
        // мы наблюдали: "Восстановление связи…" и пропадающий на середине фразы звук.
        // Креды у своего TURN временные (HMAC, см. VoiceController::turnCredentials) —
        // это безопаснее захардкоженного логина/пароля, торчащего в JS-бандле.
        async loadTurnConfig() {
            try {
                const res = await fetch('/voice/turn-credentials', { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    this.turnConfig = data.iceServers;
                    return;
                }
            } catch (e) { /* сервер недоступен — уйдём на STUN-only ниже, без TURN звонок
                              всё ещё будет работать между людьми без жёсткого NAT */ }
            this.turnConfig = [{ urls: 'stun:stun.l.google.com:19302' }];
        },

        iceServerConfig() {
            const servers = this.turnConfig || [{ urls: 'stun:stun.l.google.com:19302' }];
            // Metered отдаёт сразу 5 адресов (stun + turn-udp:80 + turn-tcp:80 + turn-udp:443
            // + turns-tcp:443) — это учетверяет число ICE-кандидатов и пар для перебора,
            // из-за чего ICE-агент может не успеть всё перебрать и застрять на "waiting"
            // (см. диагностику из webrtc-internals). Оставляем по одному самому нужному:
            // 1 stun + 1 turn(udp) + 1 turn/turns(tcp) — этого достаточно для обхода
            // почти любого NAT/файрвола, а "шума" для перебора в разы меньше.
            let stun = null, turnUdp = null, turnTcp = null;
            for (const s of servers) {
                const urls = Array.isArray(s.urls) ? s.urls : [s.urls];
                for (const url of urls) {
                    if (url.startsWith('stun:') && !stun) stun = s;
                    else if (/^turns?:/.test(url) && /transport=tcp/.test(url) && !turnTcp) turnTcp = s;
                    else if (/^turn:/.test(url) && !turnUdp) turnUdp = s;
                }
            }
            const trimmed = [stun, turnUdp, turnTcp].filter(Boolean);
            return trimmed.length ? trimmed : servers;
        },

        connectTo(userId, isInitiator) {
            if (this.peers[userId]) return;

            const pc = new RTCPeerConnection({
                iceServers: this.iceServerConfig(),
                // max-bundle/require — одно ICE-соединение на все дорожки (аудио+видео) вместо
                // отдельного на каждую: меньше кандидатов нужно перебрать, соединение
                // устанавливается быстрее, особенно на "тяжёлых" NAT.
                bundlePolicy: 'max-bundle',
                rtcpMuxPolicy: 'require',
                // iceCandidatePoolSize намеренно НЕ выставляем: в связке с несколькими
                // TURN-адресами от Metered (stun + 4 turn-варианта) он давал взрывной
                // рост числа пар кандидатов (сотни pair'ов в состоянии "waiting", которые
                // ICE-агент не успевал перебрать) — соединение застревало на
                // new => disconnected, ни разу не дойдя даже до "checking".
            });
            // "Вежливая" сторона при коллизии офферов (оба одновременно пере-согласовывают
            // соединение, например включили камеру в один момент) уступает и откатывает
            // свой оффер — иначе один из offer/answer обменов падает с "wrong state".
            pc.isPolite = !isInitiator;
            this.peers[userId] = pc;
            this.connectionState = { ...this.connectionState, [userId]: 'connecting' };

            const streamToSend = this.outgoingStream || this.localStream;
            streamToSend.getTracks().forEach(track => pc.addTrack(track, streamToSend));
            this.tuneAudioSender(pc);

            // если камера или демонстрация экрана уже включены — сразу отдаём видео-дорожку
            // новому участнику вместе с первым offer, без отдельной renegotiation
            const myVideoStream = this.localVideoStream || this.localScreenStream;
            if (myVideoStream) {
                myVideoStream.getVideoTracks().forEach(track => pc.addTrack(track, myVideoStream));
                this.tuneVideoSender(pc, this.screenSharing);
            }

            pc.onicecandidate = (e) => {
                if (e.candidate) this.sendSignal(userId, 'candidate', JSON.stringify(e.candidate));
            };

            // Если сеть моргнула (Wi-Fi/мобильный интернет переключился и т.п.) — соединение
            // переходит в 'disconnected', и часто восстанавливается САМО за пару секунд без
            // нашего вмешательства. Раньше здесь при каждом срабатывании 'failed' сразу летел
            // новый offer с iceRestart — но событие могло сработать несколько раз подряд, пока
            // предыдущий restart ещё не завершился, и офферы начинали "толкаться", из-за чего
            // соединение вместо восстановления пересобиралось заново раз за разом (отсюда
            // и долгое восстановление, и обрывы звука). Теперь: (1) на 'disconnected' даём
            // 3 секунды на самовосстановление, (2) restart запускаем максимум один раз и ждём
            // его завершения (signalingState === 'stable'), прежде чем пробовать снова.
            pc.oniceconnectionstatechange = () => {
                this.connectionState = { ...this.connectionState, [userId]: pc.iceConnectionState };

                if (pc.iceConnectionState === 'connected' || pc.iceConnectionState === 'completed') {
                    clearTimeout(pc._reconnectTimer);
                    return;
                }

                if (pc.iceConnectionState === 'disconnected') {
                    clearTimeout(pc._reconnectTimer);
                    pc._reconnectTimer = setTimeout(() => {
                        if (['disconnected', 'failed'].includes(pc.iceConnectionState)) this.restartIce(userId);
                    }, 3000);
                } else if (pc.iceConnectionState === 'failed') {
                    clearTimeout(pc._reconnectTimer);
                    this.restartIce(userId);
                }
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
                pc.createOffer().then(async offer => {
                    offer.sdp = this.tuneAudioSdp(offer.sdp);
                    await pc.setLocalDescription(offer);
                    this.sendSignal(userId, 'offer', JSON.stringify(offer));
                });
            }
        },

        // Инициирует пересогласование с iceRestart — только "невежливая" сторона
        // (иначе оба одновременно шлют offer и получается коллизия), только когда
        // предыдущее согласование уже завершилось (signalingState стабилен), и не
        // повторно, пока предыдущий restart ещё не отработал (pc._restarting).
        restartIce(userId) {
            const pc = this.peers[userId];
            if (!pc || pc.isPolite) return;
            if (pc.signalingState !== 'stable') return;
            if (pc._restarting) return;

            pc._restarting = true;
            pc.createOffer({ iceRestart: true }).then(async offer => {
                offer.sdp = this.tuneAudioSdp(offer.sdp);
                await pc.setLocalDescription(offer);
                this.sendSignal(userId, 'offer', JSON.stringify(offer));
            }).catch(() => {}).finally(() => {
                // Не снимаем блокировку мгновенно — даём время дойти offer/answer
                // обмену до конца, чтобы 'failed', сохранившийся ещё на пару тиков
                // после отправки offer, не спровоцировал второй restart поверх первого.
                setTimeout(() => { pc._restarting = false; }, 4000);
            });
        },

        disconnectFrom(userId) {
            if (this.peers[userId]) {
                clearTimeout(this.peers[userId]._reconnectTimer);
                this.peers[userId].close();
                delete this.peers[userId];
            }
            const cs = { ...this.connectionState };
            delete cs[userId];
            this.connectionState = cs;
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

        // Полный снос и пересборка соединения с нуля — когда SDP-пересогласование
        // зашло в состояние, которое браузер уже не может аккуратно распутать
        // (см. catch в handleSignal). Небольшая случайная задержка перед
        // пересозданием — чтобы обе стороны не попытались стать "инициатором"
        // ровно в один и тот же момент и не столкнулись офферами повторно.
        hardReconnect(userId) {
            this.disconnectFrom(userId);
            setTimeout(() => {
                if (this.joined && !this.peers[userId]) this.connectTo(userId, this.myId < userId);
            }, 200 + Math.random() * 400);
        },

        async sendSignal(toUserId, type, payload) {
            if (!this.channelId) return; // защитная проверка — не должно случаться, но лучше молча выйти, чем слать на /channels/null/...
            await fetch(`/channels/${this.channelId}/voice/signal`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ to_user_id: toUserId, type, payload }),
            });
        },

        async pollSignals() {
            if (!this.channelId) return;
            const channelId = this.channelId; // фиксируем на случай выхода/смены канала во время await
            const res = await fetch(`/channels/${channelId}/voice/signals?after=${this.lastSignalId}`, { headers: { 'Accept': 'application/json' } });
            if (!this.joined || this.channelId !== channelId) return; // уже вышли/сменили канал — ответ неактуален
            if (!res.ok) return;
            const signals = await res.json();
            if (!this.joined || this.channelId !== channelId) return;
            for (const sig of signals) {
                if (!this.joined || this.channelId !== channelId) return; // могли выйти прямо посреди обработки пачки сигналов
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
                try {
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
                    answer.sdp = this.tuneAudioSdp(answer.sdp);
                    await pc.setLocalDescription(answer);
                    this.sendSignal(fromId, 'answer', JSON.stringify(answer));
                } catch (e) {
                    // Несколько offer/answer-циклов наложились друг на друга (например,
                    // пересогласование из-за включения камеры + ICE-restart почти
                    // одновременно) — SDP оказался в состоянии, из которого браузер
                    // не может аккуратно продолжить (m-lines/state рассинхронизировались).
                    // Чинить такое соединение бессмысленно — проще снести и поднять
                    // заново с нуля, это надёжнее любой попытки "починить" SDP на лету.
                    this.hardReconnect(fromId);
                }
            } else if (sig.type === 'answer') {
                try {
                    // Дублирующийся/устаревший answer (например, из-за повторного опроса
                    // сигналов) вне состояния "есть свой неподтверждённый оффер" — просто игнорируем,
                    // иначе setRemoteDescription падает с "Called in wrong state: stable".
                    if (pc.signalingState === 'have-local-offer') {
                        await pc.setRemoteDescription(new RTCSessionDescription(payload));
                    }
                } catch (e) {
                    this.hardReconnect(fromId);
                }
            } else if (sig.type === 'candidate') {
                try { await pc.addIceCandidate(new RTCIceCandidate(payload)); } catch (e) { /* ignore */ }
            }
        },

        toggleMute(silent = false) {
            this.muted = !this.muted;
            if (this.localStream) this.localStream.getAudioTracks().forEach(t => t.enabled = !this.muted);
            if (!silent) this.muted ? Sounds.micMuteOn() : Sounds.micMuteOff();
        },

        toggleDeafen() {
            this.deafened = !this.deafened;
            if (this.deafened && !this.muted) this.toggleMute(true);
            Object.values(this.audioEls).forEach(a => a.muted = this.deafened);
            this.deafened ? Sounds.deafenOn() : Sounds.deafenOff();
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
            this.connectionState = {};
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
