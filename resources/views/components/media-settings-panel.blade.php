{{-- Полные настройки звука, камеры и демонстрации экрана — в отличие от компактного
     попапа в войсе, здесь есть предпросмотр камеры и параметры качества шеринга экрана.
     Живёт на странице настроек профиля (см. profile/edit.blade.php). --}}
<div
    x-data="{
        tab: 'voice',

        // --- Голос ---
        inputDevices: [],
        outputDevices: [],
        cameraDevices: [],
        selectedInput: localStorage.getItem('voice_input_device') || '',
        selectedOutput: localStorage.getItem('voice_output_device') || '',
        inputProfile: localStorage.getItem('voice_input_profile') || 'standard',
        micVolume: parseInt(localStorage.getItem('voice_mic_volume') || '100'),
        outputVolume: parseInt(localStorage.getItem('voice_output_volume') || '100'),
        sensitivity: parseInt(localStorage.getItem('voice_sensitivity') || '12'),
        testingMic: false,
        micTestLevel: 0,
        micTestStream: null,
        micTestRaf: null,

        // --- Видео (камера) ---
        selectedCamera: localStorage.getItem('video_camera_device') || '',
        videoDefaultEnabled: localStorage.getItem('video_default_enabled') === '1',
        cameraPreviewOn: false,
        cameraPreviewStream: null,

        // --- Демонстрация экрана ---
        screenResolution: localStorage.getItem('screen_share_resolution') || '1080p',
        screenFps: localStorage.getItem('screen_share_fps') || '30',
        screenAudio: localStorage.getItem('screen_share_audio') === '1',

        // --- Звуки ---
        soundsEnabled: localStorage.getItem('sound_effects_disabled') !== '1',

        async init() {
            try {
                // запрашиваем разрешение заранее, иначе label-ы устройств будут пустыми
                const tmp = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
                tmp.getTracks().forEach(t => t.stop());
            } catch (e) { /* пользователь мог уже разрешить ранее, либо откажет — не критично */ }

            const devices = await navigator.mediaDevices.enumerateDevices();
            this.inputDevices = devices.filter(d => d.kind === 'audioinput');
            this.outputDevices = devices.filter(d => d.kind === 'audiooutput');
            this.cameraDevices = devices.filter(d => d.kind === 'videoinput');
        },

        saveInput() { localStorage.setItem('voice_input_device', this.selectedInput); },
        saveOutput() {
            localStorage.setItem('voice_output_device', this.selectedOutput);
            Object.values($store.voice.audioEls).forEach(audio => {
                if (audio.setSinkId) audio.setSinkId(this.selectedOutput).catch(() => {});
            });
        },
        saveProfile() { localStorage.setItem('voice_input_profile', this.inputProfile); },
        saveMicVolume() { $store.voice.setMicVolume(this.micVolume); },
        saveOutputVolume() { $store.voice.setOutputVolume(this.outputVolume); },
        saveSensitivity() { localStorage.setItem('voice_sensitivity', this.sensitivity); },

        async startMicTest() {
            this.testingMic = true;
            const constraints = this.selectedInput ? { deviceId: { exact: this.selectedInput } } : true;
            this.micTestStream = await navigator.mediaDevices.getUserMedia({ audio: constraints });
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const source = ctx.createMediaStreamSource(this.micTestStream);
            const analyser = ctx.createAnalyser();
            analyser.fftSize = 512;
            source.connect(analyser);
            const data = new Uint8Array(analyser.frequencyBinCount);
            const tick = () => {
                analyser.getByteFrequencyData(data);
                const avg = data.reduce((s, v) => s + v, 0) / data.length;
                this.micTestLevel = Math.min(100, Math.round(avg * 1.5));
                this.micTestRaf = requestAnimationFrame(tick);
            };
            tick();
            this._micTestCtx = ctx;
        },
        stopMicTest() {
            this.testingMic = false;
            this.micTestLevel = 0;
            if (this.micTestRaf) cancelAnimationFrame(this.micTestRaf);
            if (this.micTestStream) this.micTestStream.getTracks().forEach(t => t.stop());
            if (this._micTestCtx) { try { this._micTestCtx.close(); } catch (e) {} }
        },

        async testOutput() {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            gain.gain.value = 0.2;
            osc.frequency.value = 523;
            osc.connect(gain);
            const dest = ctx.createMediaStreamDestination();
            gain.connect(dest);
            const audioEl = new Audio();
            audioEl.srcObject = dest.stream;
            audioEl.volume = this.outputVolume / 100;
            if (this.selectedOutput && audioEl.setSinkId) await audioEl.setSinkId(this.selectedOutput).catch(() => {});
            audioEl.play().catch(() => {});
            osc.start();
            setTimeout(() => { osc.stop(); ctx.close(); }, 500);
        },

        saveCamera() { localStorage.setItem('video_camera_device', this.selectedCamera); if (this.cameraPreviewOn) this.restartCameraPreview(); },
        toggleVideoDefault() {
            this.videoDefaultEnabled = !this.videoDefaultEnabled;
            localStorage.setItem('video_default_enabled', this.videoDefaultEnabled ? '1' : '0');
        },
        async startCameraPreview() {
            const constraints = this.selectedCamera ? { deviceId: { exact: this.selectedCamera }, width: { ideal: 1280 }, height: { ideal: 720 } } : { width: { ideal: 1280 }, height: { ideal: 720 } };
            try {
                this.cameraPreviewStream = await navigator.mediaDevices.getUserMedia({ video: constraints });
                this.cameraPreviewOn = true;
                this.$nextTick(() => { if (this.$refs.cameraPreview) this.$refs.cameraPreview.srcObject = this.cameraPreviewStream; });
            } catch (e) {
                alert('Не удалось получить доступ к камере: ' + e.message);
            }
        },
        stopCameraPreview() {
            this.cameraPreviewOn = false;
            if (this.cameraPreviewStream) this.cameraPreviewStream.getTracks().forEach(t => t.stop());
            this.cameraPreviewStream = null;
        },
        restartCameraPreview() { this.stopCameraPreview(); this.startCameraPreview(); },

        saveScreenSettings() {
            localStorage.setItem('screen_share_resolution', this.screenResolution);
            localStorage.setItem('screen_share_fps', this.screenFps);
        },
        toggleScreenAudio() {
            this.screenAudio = !this.screenAudio;
            localStorage.setItem('screen_share_audio', this.screenAudio ? '1' : '0');
        },

        toggleSounds() {
            this.soundsEnabled = !this.soundsEnabled;
            localStorage.setItem('sound_effects_disabled', this.soundsEnabled ? '0' : '1');
            if (this.soundsEnabled) window.Sounds?.messageReceived();
        },
    }"
    x-init="init()"
    @destroy="stopMicTest(); stopCameraPreview();"
    class="bg-[#2B2D31] rounded-lg overflow-hidden flex"
    style="min-height: 420px"
>
    {{-- Левая навигация вкладок --}}
    <nav class="w-40 flex-shrink-0 bg-[#232428] p-3">
        <p class="text-xs font-semibold uppercase text-gray-500 px-2 mb-1">Медиа</p>
        <button @click="tab = 'voice'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                :class="tab === 'voice' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
            🎙️ Голос
        </button>
        <button @click="tab = 'video'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                :class="tab === 'video' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
            📹 Видео
        </button>
        <button @click="tab = 'screen'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                :class="tab === 'screen' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
            🖥️ Демонстрация экрана
        </button>
        <button @click="tab = 'sounds'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                :class="tab === 'sounds' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
            🔔 Звуки
        </button>
    </nav>

    <div class="flex-1 p-5 overflow-y-auto">

        {{-- ГОЛОС --}}
        <div x-show="tab === 'voice'" x-cloak>
            <h3 class="font-semibold text-base mb-4">Голос</h3>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Устройство ввода</label>
            <select x-model="selectedInput" @change="saveInput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="">По умолчанию</option>
                <template x-for="d in inputDevices" :key="d.deviceId">
                    <option :value="d.deviceId" x-text="d.label || 'Микрофон ' + d.deviceId.slice(0, 6)"></option>
                </template>
            </select>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Профиль ввода</label>
            <select x-model="inputProfile" @change="saveProfile()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="standard">Стандартный (шумоподавление, эхоподавление, автогейн)</option>
                <option value="studio">Студия (без обработки сигнала)</option>
            </select>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Устройство вывода</label>
            <select x-model="selectedOutput" @change="saveOutput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="">По умолчанию</option>
                <template x-for="d in outputDevices" :key="d.deviceId">
                    <option :value="d.deviceId" x-text="d.label || 'Динамик ' + d.deviceId.slice(0, 6)"></option>
                </template>
            </select>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Громкость микрофона</label>
                    <input type="range" min="0" max="100" x-model="micVolume" @input="saveMicVolume()" class="w-full accent-[#5865F2]">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold uppercase text-gray-400">Громкость звука</label>
                        <button @click="testOutput()" class="text-[11px] text-gray-400 hover:text-white">🔊 Проверить</button>
                    </div>
                    <input type="range" min="0" max="100" x-model="outputVolume" @input="saveOutputVolume()" class="w-full accent-[#5865F2]">
                </div>
            </div>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Чувствительность микрофона</label>
            <p class="text-xs text-gray-500 mb-2">Ниже значение — индикатор "говорит" загорается от более тихого звука.</p>
            <input type="range" min="2" max="40" x-model="sensitivity" @input="saveSensitivity()" class="w-full mb-4 accent-[#5865F2]">

            <button @click="testingMic ? stopMicTest() : startMicTest()"
                    class="text-sm bg-[#3a3c42] hover:bg-[#43454b] px-4 py-2 rounded font-medium mb-2">
                <span x-text="testingMic ? 'Остановить проверку' : 'Проверка микрофона'"></span>
            </button>
            <div class="h-2 bg-[#1E1F22] rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 transition-all duration-75" :style="`width: ${micTestLevel}%`"></div>
            </div>
        </div>

        {{-- ВИДЕО --}}
        <div x-show="tab === 'video'" x-cloak>
            <h3 class="font-semibold text-base mb-4">Видео</h3>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Камера</label>
            <select x-model="selectedCamera" @change="saveCamera()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="">По умолчанию</option>
                <template x-for="d in cameraDevices" :key="d.deviceId">
                    <option :value="d.deviceId" x-text="d.label || 'Камера ' + d.deviceId.slice(0, 6)"></option>
                </template>
            </select>

            <div class="bg-black rounded-lg aspect-video mb-3 flex items-center justify-center overflow-hidden">
                <video x-show="cameraPreviewOn" x-cloak x-ref="cameraPreview" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <p x-show="!cameraPreviewOn" class="text-xs text-gray-500">Предпросмотр выключен</p>
            </div>
            <button @click="cameraPreviewOn ? stopCameraPreview() : startCameraPreview()"
                    class="text-sm bg-[#3a3c42] hover:bg-[#43454b] px-4 py-2 rounded font-medium mb-4">
                <span x-text="cameraPreviewOn ? 'Остановить предпросмотр' : 'Включить предпросмотр'"></span>
            </button>

            <label class="flex items-center justify-between cursor-pointer">
                <div>
                    <p class="text-sm font-medium">Включать камеру автоматически</p>
                    <p class="text-xs text-gray-500">Камера будет включаться сразу при входе в голосовой канал.</p>
                </div>
                <button @click="toggleVideoDefault()" class="w-11 h-6 rounded-full relative transition-colors flex-shrink-0"
                        :class="videoDefaultEnabled ? 'bg-emerald-600' : 'bg-[#3a3c42]'">
                    <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-transform"
                          :class="videoDefaultEnabled ? 'translate-x-5' : 'translate-x-0.5'"></span>
                </button>
            </label>
        </div>

        {{-- ДЕМОНСТРАЦИЯ ЭКРАНА --}}
        <div x-show="tab === 'screen'" x-cloak>
            <h3 class="font-semibold text-base mb-4">Демонстрация экрана</h3>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Качество (высота кадра)</label>
            <select x-model="screenResolution" @change="saveScreenSettings()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="720p">720p (стандартное)</option>
                <option value="1080p">1080p (Full HD)</option>
                <option value="1440p">1440p (высокое, требует больше канала)</option>
            </select>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Частота кадров</label>
            <select x-model="screenFps" @change="saveScreenSettings()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="15">15 fps (плавность не важна, экономит канал)</option>
                <option value="30">30 fps (стандарт)</option>
                <option value="60">60 fps (для динамичного контента, игр)</option>
            </select>

            <label class="flex items-center justify-between cursor-pointer mb-2">
                <div>
                    <p class="text-sm font-medium">Передавать системный звук</p>
                    <p class="text-xs text-gray-500">Собеседники услышат звук с демонстрируемого экрана. Поддержка зависит от браузера и ОС.</p>
                </div>
                <button @click="toggleScreenAudio()" class="w-11 h-6 rounded-full relative transition-colors flex-shrink-0"
                        :class="screenAudio ? 'bg-emerald-600' : 'bg-[#3a3c42]'">
                    <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-transform"
                          :class="screenAudio ? 'translate-x-5' : 'translate-x-0.5'"></span>
                </button>
            </label>

            <p class="text-xs text-gray-500 mt-4">
                Эти параметры применяются при следующем нажатии на 🖥️ в голосовом канале —
                браузер сам предложит выбрать окно, экран или вкладку для показа.
            </p>
        </div>

        {{-- ЗВУКИ --}}
        <div x-show="tab === 'sounds'" x-cloak>
            <h3 class="font-semibold text-base mb-4">Уведомления и звуки</h3>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium">Звуковые уведомления</p>
                    <p class="text-xs text-gray-500">Звук при входе/выходе из войса, новом сообщении и упоминании.</p>
                </div>
                <button @click="toggleSounds()" class="w-11 h-6 rounded-full relative transition-colors flex-shrink-0"
                        :class="soundsEnabled ? 'bg-emerald-600' : 'bg-[#3a3c42]'">
                    <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white transition-transform"
                          :class="soundsEnabled ? 'translate-x-5' : 'translate-x-0.5'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
