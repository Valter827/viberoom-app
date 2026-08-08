{{-- Настройки голоса и звуков: оконный режим с вкладками слева, закрывается крестиком —
     как в Discord. Открывается из шестерёнки в постоянной полосе голосового звонка. --}}
<div
    x-data="{
        show: false,
        tab: 'voice',
        inputDevices: [],
        outputDevices: [],
        selectedInput: localStorage.getItem('voice_input_device') || '',
        selectedOutput: localStorage.getItem('voice_output_device') || '',
        sensitivity: parseInt(localStorage.getItem('voice_sensitivity') || '12'),
        soundsEnabled: localStorage.getItem('sound_effects_disabled') !== '1',
        testing: false,
        testLevel: 0,
        testStream: null,
        testCtx: null,
        testRaf: null,

        async open() {
            this.show = true;
            this.tab = 'voice';
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
        saveSensitivity() {
            localStorage.setItem('voice_sensitivity', this.sensitivity);
        },
        toggleSounds() {
            this.soundsEnabled = !this.soundsEnabled;
            localStorage.setItem('sound_effects_disabled', this.soundsEnabled ? '0' : '1');
            if (this.soundsEnabled) window.Sounds?.messageReceived();
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
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/60 vr-backdrop flex items-center justify-center z-50"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.outside="close()" class="bg-[#313338] rounded-lg overflow-hidden w-[520px] max-w-[90vw] flex" style="height: 480px"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Левая навигация, как в оконных настройках Discord --}}
            <nav class="w-40 flex-shrink-0 bg-[#2B2D31] p-3">
                <p class="text-xs font-semibold uppercase text-gray-500 px-2 mb-1">Настройки</p>
                <button @click="tab = 'voice'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                        :class="tab === 'voice' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                    <x-icon name="mic" class="w-4 h-4 inline -mt-0.5 mr-1" /> Голос
                </button>
                <button @click="tab = 'sounds'" class="w-full text-left px-2 py-1.5 rounded text-sm mb-0.5"
                        :class="tab === 'sounds' ? 'bg-[#404249] text-white' : 'text-gray-400 hover:bg-[#35373c] hover:text-gray-200'">
                    <x-icon name="bell" class="w-4 h-4 inline -mt-0.5 mr-1" /> Звуки
                </button>
            </nav>

            {{-- Контент --}}
            <div class="flex-1 flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-black/20">
                    <h3 class="font-semibold text-lg" x-text="tab === 'voice' ? 'Голос' : 'Уведомления и звуки'"></h3>
                    <button @click="close()" class="icon-action" title="Закрыть"><x-icon name="x" class="w-4 h-4" /></button>
                </div>

                <div class="flex-1 overflow-y-auto p-5">

                    {{-- Вкладка: Голос --}}
                    <div x-show="tab === 'voice'" x-cloak>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1 flex items-center gap-1"><x-icon name="mic" class="w-3.5 h-3.5" /> Микрофон</label>
                        <select x-model="selectedInput" @change="saveInput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                            <option value="">По умолчанию</option>
                            <template x-for="d in inputDevices" :key="d.deviceId">
                                <option :value="d.deviceId" x-text="d.label || 'Микрофон ' + d.deviceId.slice(0, 6)"></option>
                            </template>
                        </select>

                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1 flex items-center gap-1"><x-icon name="volume-2" class="w-3.5 h-3.5" /> Динамики</label>
                        <select x-model="selectedOutput" @change="saveOutput()" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                            <option value="">По умолчанию</option>
                            <template x-for="d in outputDevices" :key="d.deviceId">
                                <option :value="d.deviceId" x-text="d.label || 'Динамик ' + d.deviceId.slice(0, 6)"></option>
                            </template>
                        </select>

                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">
                            Чувствительность микрофона
                        </label>
                        <p class="text-xs text-gray-500 mb-2">Ниже значение — индикатор "говорит" загорается от более тихого звука.</p>
                        <input type="range" min="2" max="40" x-model="sensitivity" @input="saveSensitivity()" class="w-full mb-4 accent-[#5865F2]">

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
                    </div>

                    {{-- Вкладка: Звуки --}}
                    <div x-show="tab === 'sounds'" x-cloak>
                        <div class="flex items-center justify-between mb-4">
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

                <div class="p-4 border-t border-black/20">
                    <button @click="close()" class="btn-lift w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                        Готово
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
