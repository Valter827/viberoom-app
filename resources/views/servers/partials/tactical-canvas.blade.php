{{-- Тактический Оверлей: выдвижная мини-доска поверх чата с заготовками карт
     популярных игр. Штрихи хранятся как проценты от размера холста и
     синхронизируются тем же polling-подходом, что и остальной чат —
     см. TacticalController и методы tactical* в chat-area.blade.php. --}}
<div x-show="showTactical" x-cloak
     class="absolute inset-0 z-30 bg-[#1E1F22]/98 flex flex-col"
     x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    {{-- Тулбар --}}
    <div class="h-12 flex items-center gap-2 px-3 border-b border-black/30 flex-shrink-0 flex-wrap">
        <span class="text-sm font-semibold mr-2 flex items-center gap-1.5"><x-icon name="map" class="w-4 h-4" /> Тактический оверлей</span>

        {{-- Выбор карты --}}
        <div class="flex items-center gap-1 bg-[#2B2D31] rounded-lg p-1">
            @foreach (['blank' => 'Пусто', 'dota' => 'Dota 2', 'cs' => 'CS', 'valorant' => 'Valorant', 'rust' => 'Rust'] as $key => $label)
                <button type="button" @click="tacticalSetMap('{{ $key }}')"
                        class="text-xs px-2 py-1 rounded"
                        :class="tacticalMapKey === '{{ $key }}' ? 'bg-[#5865F2] text-white' : 'text-gray-400 hover:text-white'">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Инструмент --}}
        <div class="flex items-center gap-1 bg-[#2B2D31] rounded-lg p-1">
            <button type="button" @click="tacticalTool = 'pen'" class="text-xs px-2 py-1 rounded" :class="tacticalTool === 'pen' ? 'bg-[#5865F2] text-white' : 'text-gray-400 hover:text-white'"><x-icon name="pencil" class="w-3.5 h-3.5 inline -mt-0.5" /> Перо</button>
            <button type="button" @click="tacticalTool = 'line'" class="text-xs px-2 py-1 rounded" :class="tacticalTool === 'line' ? 'bg-[#5865F2] text-white' : 'text-gray-400 hover:text-white'"><x-icon name="ruler" class="w-3.5 h-3.5 inline -mt-0.5" /> Линия</button>
            <button type="button" @click="tacticalTool = 'arrow'" class="text-xs px-2 py-1 rounded" :class="tacticalTool === 'arrow' ? 'bg-[#5865F2] text-white' : 'text-gray-400 hover:text-white'"><x-icon name="arrow-right" class="w-3.5 h-3.5 inline -mt-0.5" /> Стрелка</button>
        </div>

        {{-- Цвет --}}
        <div class="flex items-center gap-1">
            @foreach (['#f23f42' => 'красный', '#3ba55d' => 'зелёный', '#5865F2' => 'синий', '#faa61a' => 'жёлтый', '#ffffff' => 'белый'] as $hex => $name)
                <button type="button" @click="tacticalColor = '{{ $hex }}'" :title="'{{ $name }}'"
                        class="w-5 h-5 rounded-full border-2"
                        :class="tacticalColor === '{{ $hex }}' ? 'border-white' : 'border-transparent'"
                        style="background-color: {{ $hex }}"></button>
            @endforeach
        </div>

        {{-- Толщина --}}
        <input type="range" min="1" max="8" x-model.number="tacticalWidth" class="w-16">

        <div class="flex-1"></div>

        <button type="button" @click="tacticalClear()" class="text-xs text-red-400 hover:bg-red-500/10 px-2 py-1.5 rounded"><x-icon name="trash-2" class="w-3.5 h-3.5 inline -mt-0.5 mr-1" /> Очистить</button>
        <button type="button" @click="showTactical = false; closeTactical()" class="icon-action !w-7 !h-7" title="Закрыть"><x-icon name="x" class="w-3.5 h-3.5" /></button>
    </div>

    {{-- Холст --}}
    <div class="flex-1 relative overflow-hidden select-none touch-none"
         @pointerdown="tacticalPointerDown($event)"
         @pointermove="tacticalPointerMove($event)"
         @pointerup="tacticalPointerUp($event)"
         @pointerleave="tacticalDrawing && tacticalPointerUp($event)">

        {{-- Схематичные заготовки карт — обобщённые зоны, а не копии реальных
             ассетов игр, просто чтобы было от чего оттолкнуться при наброске. --}}
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 w-full h-full pointer-events-none opacity-70">
            <rect width="100" height="100" fill="#26282c"/>
            <template x-if="tacticalMapKey === 'dota'">
                <g>
                    <rect x="4" y="72" width="20" height="20" rx="2" fill="#2f5a35"/>
                    <text x="14" y="83" font-size="3.2" fill="#9fd3a8" text-anchor="middle">Radiant</text>
                    <rect x="76" y="4" width="20" height="20" rx="2" fill="#6b2b2b"/>
                    <text x="86" y="15" font-size="3.2" fill="#e3a6a6" text-anchor="middle">Dire</text>
                    <line x1="8" y1="8" x2="92" y2="8" stroke="#4a4d55" stroke-width="1.5"/>
                    <line x1="8" y1="8" x2="8" y2="92" stroke="#4a4d55" stroke-width="1.5"/>
                    <line x1="8" y1="92" x2="92" y2="92" stroke="#4a4d55" stroke-width="1.5"/>
                    <line x1="92" y1="8" x2="92" y2="92" stroke="#4a4d55" stroke-width="1.5"/>
                    <line x1="12" y1="88" x2="88" y2="12" stroke="#4a4d55" stroke-width="1.2" stroke-dasharray="2,2"/>
                </g>
            </template>
            <template x-if="tacticalMapKey === 'cs'">
                <g>
                    <rect x="8" y="8" width="26" height="22" rx="2" fill="#5a4a24"/>
                    <text x="21" y="21" font-size="3.4" fill="#e8cf8f" text-anchor="middle">Site A</text>
                    <rect x="66" y="70" width="26" height="22" rx="2" fill="#24405a"/>
                    <text x="79" y="83" font-size="3.4" fill="#8fc0e8" text-anchor="middle">Site B</text>
                    <rect x="42" y="42" width="16" height="16" rx="2" fill="#3a3d44"/>
                    <text x="50" y="52" font-size="3" fill="#c8cad0" text-anchor="middle">Mid</text>
                </g>
            </template>
            <template x-if="tacticalMapKey === 'valorant'">
                <g>
                    <rect x="8" y="66" width="26" height="26" rx="2" fill="#5a2430"/>
                    <text x="21" y="80" font-size="3.4" fill="#e88fa0" text-anchor="middle">Site A</text>
                    <rect x="66" y="8" width="26" height="26" rx="2" fill="#245a4a"/>
                    <text x="79" y="22" font-size="3.4" fill="#8fe8cf" text-anchor="middle">Site B</text>
                    <rect x="42" y="42" width="16" height="16" rx="2" fill="#3a3d44"/>
                    <text x="50" y="52" font-size="3" fill="#c8cad0" text-anchor="middle">Mid</text>
                </g>
            </template>
            <template x-if="tacticalMapKey === 'rust'">
                <g>
                    <circle cx="50" cy="50" r="14" fill="#5a4a24"/>
                    <text x="50" y="51" font-size="3.2" fill="#e8cf8f" text-anchor="middle">Монумент</text>
                    <rect x="14" y="14" width="14" height="14" rx="1" fill="#3a3d44"/>
                    <text x="21" y="24" font-size="2.6" fill="#c8cad0" text-anchor="middle">База</text>
                    <line x1="21" y1="28" x2="42" y2="46" stroke="#5a5d64" stroke-width="1" stroke-dasharray="1.5,1.5"/>
                    <line x1="79" y1="79" x2="58" y2="58" stroke="#5a5d64" stroke-width="1" stroke-dasharray="1.5,1.5"/>
                </g>
            </template>
        </svg>

        {{-- Слой штрихов --}}
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 w-full h-full pointer-events-none">
            <defs>
                <marker id="arrowhead" markerWidth="4" markerHeight="4" refX="3" refY="2" orient="auto">
                    <polygon points="0 0, 4 2, 0 4" fill="context-stroke"/>
                </marker>
            </defs>
            {{-- Alpine's x-for теряет scope переменной внутри <svg> (известный баг:
                 https://github.com/alpinejs/alpine/issues/2078), поэтому штрихи
                 рендерим не через x-for/x-if, а строкой через x-html --}}
            <g x-html="renderTacticalStrokes()"></g>
        </svg>
    </div>
</div>
