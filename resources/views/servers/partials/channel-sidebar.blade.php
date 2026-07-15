{{-- Вторая колонка: название сервера + список каналов, сгруппированных по категориям --}}
<aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">

    {{-- Шапка с названием сервера --}}
    <div class="h-12 flex items-center justify-between px-4 shadow-sm border-b border-black/20 flex-shrink-0">
        <h1 class="font-semibold truncate">{{ $server->name }}</h1>
        <div class="flex items-center gap-3">
            @php $myRole = $server->members->firstWhere('id', Auth::id())?->pivot->role; @endphp
            @if (in_array($myRole, ['owner', 'admin']))
                <a href="{{ route('servers.edit', $server) }}" class="text-gray-400 hover:text-white text-xs" title="Настройки сервера">⚙️</a>
            @endif
            <button
                x-data
                @click="navigator.clipboard.writeText('{{ $server->invite_code }}'); $dispatch('notify', 'Код приглашения скопирован')"
                class="text-gray-400 hover:text-white text-xs" title="Скопировать код приглашения">
                📋
            </button>
        </div>
    </div>

    {{-- Список категорий и каналов --}}
    <div class="flex-1 overflow-y-auto px-2 py-3 space-y-4">
        @foreach ($server->categories as $category)
            <div>
                <h2 class="px-2 text-xs font-semibold uppercase text-gray-400 tracking-wide mb-1">
                    {{ $category->name }}
                </h2>
                <div class="space-y-0.5">
                    @foreach ($category->channels as $channel)
                        @include('servers.partials.channel-link', ['channel' => $channel])
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Каналы без категории --}}
        @if ($server->channels->whereNull('category_id')->count())
            <div class="space-y-0.5">
                @foreach ($server->channels->whereNull('category_id') as $channel)
                    @include('servers.partials.channel-link', ['channel' => $channel])
                @endforeach
            </div>
        @endif
    </div>

    {{-- Мини-профиль текущего пользователя внизу колонки --}}
    <div class="h-14 flex items-center px-2 bg-[#232428] flex-shrink-0">
        <div class="relative">
            <img src="{{ Auth::user()->avatar_url }}" class="w-8 h-8 rounded-full" alt="avatar">
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-[#232428]
                {{ Auth::user()->status === 'online' ? 'bg-green-500' : (Auth::user()->status === 'idle' ? 'bg-yellow-500' : (Auth::user()->status === 'dnd' ? 'bg-red-500' : 'bg-gray-500')) }}">
            </span>
        </div>
        <div class="ml-2 leading-tight overflow-hidden">
            <p class="text-sm font-medium truncate">{{ Auth::user()->name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ ucfirst(Auth::user()->status) }}</p>
        </div>
        <a href="{{ route('profile.edit') }}" class="ml-auto text-gray-400 hover:text-white text-sm" title="Настройки профиля">⚙️</a>
    </div>
</aside>
