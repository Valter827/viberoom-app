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
        <a href="{{ route('servers.show', $s) }}"
           class="group relative w-12 h-12 flex items-center justify-center rounded-full overflow-hidden hover:rounded-2xl transition-all duration-200
                  {{ isset($server) && $server->id === $s->id ? 'rounded-2xl ring-2 ring-[#5865F2]' : '' }}">
            <img src="{{ $s->iconUrl() }}" alt="{{ $s->name }}" class="w-full h-full object-cover">

            {{-- Тултип с названием сервера при наведении --}}
            <span class="pointer-events-none absolute left-16 whitespace-nowrap bg-black/90 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity z-50">
                {{ $s->name }}
            </span>
        </a>
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
