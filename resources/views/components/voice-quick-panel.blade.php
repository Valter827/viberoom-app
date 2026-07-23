{{-- Содержимое компактной всплывающей панели настроек голоса (см. channel-sidebar.blade.php) --}}
<div
    x-data="{
        inputDevices: [],
        outputDevices: [],
        selectedInput: localStorage.getItem('voice_input_device') || '',
        selectedOutput: localStorage.getItem('voice_output_device') || '',
        inputProfile: localStorage.getItem('voice_input_profile') || 'standard',
        micVolume: parseInt(localStorage.getItem('voice_mic_volume') || '100'),
        outputVolume: parseInt(localStorage.getItem('voice_output_volume') || '100'),
        level: 0,
        levelRaf: null,

        async init() {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.inputDevices = devices.filter(d => d.kind === 'audioinput');
            this.outputDevices = devices.filter(d => d.kind === 'audiooutput');
            this.trackLevel();
        },

        // живой индикатор уровня микрофона, пока звонок активен — берём тот же
        // локальный поток, что уже используется для звонка, ничего заново не запрашиваем
        trackLevel() {
            const tick = () => {
                const stream = $store.voice.localStream;
                if (stream && $store.voice.joined) {
                    if (!this._analyser || this._streamRef !== stream) {
                        this._streamRef = stream;
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const source = ctx.createMediaStreamSource(stream);
                        this._analyser = ctx.createAnalyser();
                        this._analyser.fftSize = 512;
                        source.connect(this._analyser);
                        this._data = new Uint8Array(this._analyser.frequencyBinCount);
                    }
                    this._analyser.getByteFrequencyData(this._data);
                    const avg = this._data.reduce((s, v) => s + v, 0) / this._data.length;
                    this.level = Math.min(100, Math.round(avg * 2));
                }
                this.levelRaf = requestAnimationFrame(tick);
            };
            tick();
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

        // Короткий тестовый сигнал именно через выбранное устройство вывода —
        // помогает проверить, что «Динамики» в списке выбраны правильно, не отходя от попапа.
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
            if (this.selectedOutput && audioEl.setSinkId) {
                await audioEl.setSinkId(this.selectedOutput).catch(() => {});
            }
            audioEl.play().catch(() => {});
            osc.start();
            setTimeout(() => { osc.stop(); ctx.close(); }, 500);
        },
    }"
    x-init="init()"
    @destroy="if (levelRaf) cancelAnimationFrame(levelRaf)"
>
    <label class="block text-[11px] font-semibold uppercase text-gray-400 mb-1">Устройство ввода</label>
    <select x-model="selectedInput" @change="saveInput()" class="w-full bg-[#1E1F22] rounded px-2 py-1.5 text-xs outline-none mb-3">
        <option value="">По умолчанию</option>
        <template x-for="d in inputDevices" :key="d.deviceId">
            <option :value="d.deviceId" x-text="d.label || 'Микрофон ' + d.deviceId.slice(0, 6)"></option>
        </template>
    </select>

    <label class="block text-[11px] font-semibold uppercase text-gray-400 mb-1">Профиль ввода</label>
    <select x-model="inputProfile" @change="saveProfile()" class="w-full bg-[#1E1F22] rounded px-2 py-1.5 text-xs outline-none mb-3">
        <option value="standard">Стандартный (шумоподавление)</option>
        <option value="studio">Студия (без обработки)</option>
    </select>

    <label class="block text-[11px] font-semibold uppercase text-gray-400 mb-1">Устройство вывода</label>
    <select x-model="selectedOutput" @change="saveOutput()" class="w-full bg-[#1E1F22] rounded px-2 py-1.5 text-xs outline-none mb-3">
        <option value="">По умолчанию</option>
        <template x-for="d in outputDevices" :key="d.deviceId">
            <option :value="d.deviceId" x-text="d.label || 'Динамик ' + d.deviceId.slice(0, 6)"></option>
        </template>
    </select>

    <label class="block text-[11px] font-semibold uppercase text-gray-400 mb-1">Громкость микрофона</label>
    <input type="range" min="0" max="100" x-model="micVolume" @input="saveMicVolume()" class="w-full mb-1 accent-[#5865F2]">

    <label class="block text-[11px] font-semibold uppercase text-gray-400 mb-1 mt-2">Входная чувствительность</label>
    <div class="flex gap-0.5 mb-3 h-3 items-end">
        <template x-for="i in 30" :key="i">
            <div class="flex-1 rounded-sm transition-colors duration-75"
                 :class="(i / 30 * 100) < level ? 'bg-emerald-500' : 'bg-[#2b2d31]'"
                 style="height: 100%"></div>
        </template>
    </div>

    <div class="flex items-center justify-between mb-1">
        <label class="block text-[11px] font-semibold uppercase text-gray-400">Громкость звука</label>
        <button @click="testOutput()" class="text-[11px] text-gray-400 hover:text-white">🔊 Проверить</button>
    </div>
    <input type="range" min="0" max="100" x-model="outputVolume" @input="saveOutputVolume()" class="w-full mb-3 accent-[#5865F2]">

    <label class="flex items-center gap-2 mb-3 cursor-pointer">
        <input type="checkbox" :checked="$store.voice.muted" @change="$store.voice.toggleMute()" class="accent-[#5865F2]">
        <span class="text-xs text-gray-300">Откл. звук</span>
    </label>

    <button @click="$dispatch('open-voice-settings')" class="group flex items-center gap-2 text-xs text-gray-400 hover:text-white transition-colors">
        <span class="inline-block transition-transform duration-300 group-hover:rotate-90">⚙️</span> Настройки голоса
    </button>
</div>
