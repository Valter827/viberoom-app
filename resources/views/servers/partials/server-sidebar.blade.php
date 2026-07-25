{{-- Первая колонка: список серверов пользователя, как в Discord --}}
<aside class="w-[72px] flex-shrink-0 bg-[#1E1F22] flex flex-col items-center py-3 space-y-2 overflow-y-auto">

    {{-- "Домашняя" кнопка / личные сообщения (заглушка для базовой версии) --}}
    <a href="{{ route('servers.create') }}"
       class="group relative w-12 h-12 flex items-center justify-center rounded-full bg-[#313338] hover:bg-[#5865F2] hover:rounded-2xl transition-all duration-200"
       title="Создать сервер">
        <span class="text-2xl text-green-400 group-hover:text-white">+</span>
    </a>

    <div class="w-8 border-t border-[#3a3c42]"></div>

    @foreach (Auth::user()->servers as $s)
        @php
            $sRole = $s->pivot->role;
            $serverKey = 'server-' . $s->id;
        @endphp
        {{-- Обёртка с собственным x-data — состояние меню (ПКМ) + локальный mute-тумблер --}}
        <div class="relative"
             x-data="{
                key: @js($serverKey),
                muted: JSON.parse(localStorage.getItem('muted_servers') || '[]').includes({{ $s->id }}),
                menuOpen: false,
                menuX: 0,
                menuY: 0,
                openMenu(e) {
                    $store.contextMenu.activate(this.key);
                    this.menuOpen = true;
                    this.menuX = e.clientX;
                    this.menuY = e.clientY;
                },
                closeMenu() {
                    this.menuOpen = false;
                    $store.contextMenu.deactivate(this.key);
                },
                toggleMute() {
                    let list = JSON.parse(localStorage.getItem('muted_servers') || '[]');
                    list = this.muted ? list.filter(id => id !== {{ $s->id }}) : [...list, {{ $s->id }}];
                    localStorage.setItem('muted_servers', JSON.stringify(list));
                    this.muted = !this.muted;
                    $dispatch('notify', this.muted ? 'Сервер заглушён' : 'Звук сервера включён');
                },
             }"
             x-init="$watch('$store.contextMenu.activeKey', (v) => { if (v !== key) { menuOpen = false; } })"
             @keydown.escape.window="menuOpen = false; $store.contextMenu.deactivate(key)">
            <a href="{{ route('servers.show', $s) }}"
               @contextmenu.prevent="openMenu($event)"
               class="group relative w-12 h-12 flex items-center justify-center rounded-full overflow-hidden hover:rounded-2xl transition-all duration-200
                      {{ isset($server) && $server->id === $s->id ? 'rounded-2xl ring-2 ring-[#5865F2]' : '' }}">
                <img src="{{ $s->iconUrl() }}" alt="{{ $s->name }}" class="w-full h-full object-cover">

                {{-- Тултип с названием сервера при наведении --}}
                <span class="pointer-events-none absolute left-16 whitespace-nowrap bg-black/90 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50">
                    {{ $s->name }}
                </span>
            </a>

            {{-- Контекстное меню сервера (правый клик), как в Discord.
                 Телепортируется в <body>, иначе overflow-y-auto родительского
                 сайдбара обрезает меню, если оно выходит за границы узкой колонки. --}}
            <template x-teleport="body">
            <div x-show="menuOpen" x-cloak
                 x-transition:enter="ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.outside="closeMenu()"
                 :style="`top: ${menuY}px; left: ${menuX}px;`"
                 class="fixed z-50 w-64 bg-[#111214] rounded-lg shadow-2xl py-1.5 text-sm origin-top-left">
                <p class="px-3 py-1.5 text-xs font-semibold text-gray-500 truncate">{{ $s->name }}</p>

                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $s->invite_code }}'); $dispatch('notify', 'Код приглашения скопирован'); closeMenu()"
                        class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                    📨 Пригласить людей
                </button>

                @if (in_array($sRole, ['owner', 'admin', 'moderator']))
                    <a href="{{ route('servers.edit', $s) }}" class="block px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                        ⚙️ Настройки сервера
                    </a>
                @endif

                @if (isset($server) && $server->id === $s->id && in_array($sRole, ['owner', 'admin']))
                    <button type="button" @click="$dispatch('open-create-channel', null); closeMenu()"
                            class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                        ➕ Создать канал
                    </button>
                @endif

                <div class="my-1 border-t border-black/30"></div>

                {{-- Отключает только звук упоминаний с этого сервера (localStorage) —
                     сам счётчик непрочитанных продолжает обновляться, как и в Discord. --}}
                <button type="button" @click="toggleMute()"
                        class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                    <span x-show="!muted">🔕 Заглушить сервер</span>
                    <span x-show="muted" x-cloak>🔔 Включить звук сервера</span>
                </button>

                <div class="my-1 border-t border-black/30"></div>

                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $s->id }}'); $dispatch('notify', 'ID сервера скопирован'); closeMenu()"
                        class="w-full text-left px-3 py-1.5 text-gray-300 hover:bg-[#5865F2] hover:text-white">
                    🆔 Скопировать ID сервера
                </button>

                <div class="my-1 border-t border-black/30"></div>

                @if ($sRole === 'owner')
                    <form method="POST" action="{{ route('servers.destroy', $s) }}"
                          onsubmit="return confirm('Удалить сервер «{{ $s->name }}» без возможности восстановления?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-left px-3 py-1.5 text-red-400 hover:bg-red-500 hover:text-white">
                            🗑️ Удалить сервер
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('servers.leave', $s) }}"
                          onsubmit="return confirm('Выйти с сервера «{{ $s->name }}»?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-left px-3 py-1.5 text-red-400 hover:bg-red-500 hover:text-white">
                            🚪 Выйти из сервера
                        </button>
                    </form>
                @endif
            </div>
            </template>
        </div>
    @endforeach

    {{-- Кнопка присоединиться по коду приглашения --}}
    <button
        x-data
        @click="$dispatch('open-join-modal')"
        class="w-12 h-12 flex items-center justify-center rounded-full bg-[#313338] hover:bg-[#3ba55c] hover:rounded-2xl transition-all duration-200 text-xl text-green-400 hover:text-white"
        title="Присоединиться к серверу">
        #
    </button>
</aside>
