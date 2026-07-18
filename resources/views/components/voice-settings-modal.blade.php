{{-- Настройки голоса: выбор микрофона/динамика + проверка микрофона.
     Открывается из шестерёнки в постоянной полосе голосового звонка. --}}
<div
    x-data="{
        show: false,
        inputDevices: [],
        outputDevices: [],
        selectedInput: localStorage.getItem('voice_input_device') || '',
        selectedOutput: localStorage.getItem('voice_output_device') || '',
        testing: false,
        testLevel: 0,
        testStream: null,
        testCtx: null,
        testRaf: null,

        async open() {
            this.show = true;
            try {
                // запрашиваем разрешение, иначе labels у устройств будут пустыми
                const tmp = await navigator.mediaDevices.getUserMedia({ audio: true });
                tmp.getTracks().forEach(t => t.stop());
            } catch (e) { /* пользователь мог уже разрешить ранее */ }

            const devices = await navigator.mediaDevices.enumerateDevices();
            this.inputDevices = devices.filter(d => d.kind === 'audioinput');
            this.outputDevices = devices.filter(d => d.kind === 'audiooutput');
        },

        saveInput() {
            localStorage.setItem('voice_input_device', this.selectedInput);
        },
        saveOutput() {
            localStorage.setItem('voice_output_device', this.selectedOutput);
            // применяем сразу ко всем текущим аудио-элементам собеседников, если браузер поддерживает setSinkId
            Object.values($store.voice.audioEls).forEach(audio => {
                if (audio.setSinkId) audio.setSinkId(this.selectedOutput).catch(() => {});
            });
        },

        async startTest() {
            this.testing = true;
            const constraints = this.selectedInput ? { deviceId: { exact: this.selectedInput } } : true;
            this.testStream = await navigator.mediaDevices.getUserMedia({ audio: constraints });
            this.testCtx = new (window.AudioContext || window.webkitAudioContext)();
            const source = this.testCtx.createMediaStreamSource(this.testStream);
            const analyser = this.testCtx.createAnalyser();
            analyser.fftSize = 512;
            source.connect(analyser);
            const data = new Uint8Array(analyser.frequencyBinCount);

            const tick = () => {
                analyser.getByteFrequencyData(data);
                const avg = data.reduce((s, v) => s + v, 0) / data.length;
                this.testLevel = Math.min(100, Math.round(avg * 1.5));
                this.testRaf = requestAnimationFrame(tick);
            };
            tick();
        },

        stopTest() {
            this.testing = false;
            this.testLevel = 0;
            if (this.testRaf) cancelAnimationFrame(this.testRaf);
            if (this.testStream) this.testStream.getTracks().forEach(t => t.stop());
            if (this.testCtx) { try { this.testCtx.close(); } catch (e) {} }
        },

        close() {
            this.stopTest();
            this.show = false;
        },
    }"
    @open-voice-settings.window="open()"
>
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div @click.outside="close()" class="bg-[#313338] rounded-lg p-6 w-[420px]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-lg">Настройки голоса</h3>
                <button @click="close()" class="text-gray-400 hover:text-white">✕</button>
            </div>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">🎙️ Микрофон</label>
            <select x-model="selectedInput" @change="saveInput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="">По умолчанию</option>
                <template x-for="d in inputDevices" :key="d.deviceId">
                    <option :value="d.deviceId" x-text="d.label || 'Микрофон ' + d.deviceId.slice(0, 6)"></option>
                </template>
            </select>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">🔊 Динамики</label>
            <select x-model="selectedOutput" @change="saveOutput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                <option value="">По умолчанию</option>
                <template x-for="d in outputDevices" :key="d.deviceId">
                    <option :value="d.deviceId" x-text="d.label || 'Динамик ' + d.deviceId.slice(0, 6)"></option>
                </template>
            </select>

            <div class="mb-2">
                <button @click="testing ? stopTest() : startTest()"
                        class="text-sm bg-[#3a3c42] hover:bg-[#43454b] px-4 py-2 rounded font-medium mb-2">
                    <span x-text="testing ? 'Остановить проверку' : 'Проверка микрофона'"></span>
                </button>
                <div class="h-2 bg-[#1E1F22] rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 transition-all duration-75" :style="`width: ${testLevel}%`"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1" x-show="testing">Скажите что-нибудь — полоса должна двигаться.</p>
            </div>

            <p class="text-xs text-gray-500 mt-4">
                Изменения применятся к текущему звонку сразу, а к новым — автоматически при следующем подключении.
            </p>

            <button @click="close()" class="w-full mt-4 bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                Готово
            </button>
        </div>
    </div>
</div>
