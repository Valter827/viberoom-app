{{-- Вторая колонка: название сервера + список каналов, сгруппированных по категориям --}}
<aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">

    {{-- Шапка с названием сервера --}}
    <div class="h-12 flex items-center justify-between px-4 shadow-sm border-b border-black/20 flex-shrink-0">
        <h1 class="font-semibold truncate">{{ $server->name }}</h1>
        <div class="flex items-center gap-3">
            @php $myRole = $server->members->firstWhere('id', Auth::id())?->pivot->role; @endphp
            @include('components.mentions-bell')
            @if (in_array($myRole, ['owner', 'admin', 'moderator']))
                <a href="{{ route('servers.edit', $server) }}" class="text-gray-400 hover:text-white text-xs" title="Участники и настройки">👥</a>
            @endif
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
    <div class="flex-1 overflow-y-auto px-2 py-3 space-y-4"
         x-data="{ showCreate: false, targetCategoryId: null }"
         @open-create-channel.window="showCreate = true; targetCategoryId = $event.detail">
        @foreach ($server->categories as $category)
            <div>
                <div class="flex items-center justify-between px-2 mb-1 group">
                    <h2 class="text-xs font-semibold uppercase text-gray-400 tracking-wide">
                        {{ $category->name }}
                    </h2>
                    @if (in_array($myRole, ['owner', 'admin']))
                        <button @click="$dispatch('open-create-channel', {{ $category->id }})"
                                class="text-gray-500 hover:text-white opacity-0 group-hover:opacity-100 text-sm" title="Создать канал">+</button>
                    @endif
                </div>
                <div class="space-y-0.5">
                    @foreach ($category->channels as $channel)
                        @include('servers.partials.channel-link', ['channel' => $channel])
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Каналы без категории --}}
        <div>
            @if (in_array($myRole, ['owner', 'admin']))
                <div class="flex items-center justify-between px-2 mb-1 group">
                    <h2 class="text-xs font-semibold uppercase text-gray-400 tracking-wide">Каналы</h2>
                    <button @click="$dispatch('open-create-channel', null)"
                            class="text-gray-500 hover:text-white opacity-0 group-hover:opacity-100 text-sm" title="Создать канал">+</button>
                </div>
            @endif
            <div class="space-y-0.5">
                @foreach ($server->channels->whereNull('category_id') as $channel)
                    @include('servers.partials.channel-link', ['channel' => $channel])
                @endforeach
            </div>
        </div>

        {{-- Модалка создания канала --}}
        <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
            <div @click.outside="showCreate = false" class="bg-[#313338] rounded-lg p-6 w-96">
                <h3 class="font-semibold mb-4">Создать канал</h3>
                <form method="POST" action="{{ route('channels.store', $server) }}">
                    @csrf
                    <template x-if="targetCategoryId">
                        <input type="hidden" name="category_id" :value="targetCategoryId">
                    </template>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Тип канала</label>
                    <div class="flex gap-2 mb-4" x-data="{ type: 'text' }">
                        <label class="flex-1 flex items-center gap-2 bg-[#1E1F22] rounded px-3 py-2 cursor-pointer"
                               :class="type === 'text' ? 'ring-1 ring-[#5865F2]' : ''">
                            <input type="radio" name="type" value="text" x-model="type" class="accent-[#5865F2]">
                            <span class="text-sm">💬 Текстовый</span>
                        </label>
                        <label class="flex-1 flex items-center gap-2 bg-[#1E1F22] rounded px-3 py-2 cursor-pointer"
                               :class="type === 'voice' ? 'ring-1 ring-[#5865F2]' : ''">
                            <input type="radio" name="type" value="voice" x-model="type" class="accent-[#5865F2]">
                            <span class="text-sm">🔊 Голосовой</span>
                        </label>
                    </div>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название канала</label>
                    <input type="text" name="name" required maxlength="50" placeholder="новый-канал"
                           class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreate = false" class="text-sm text-gray-400 hover:text-white">Отмена</button>
                        <button type="submit" class="text-sm bg-[#5865F2] hover:bg-[#4752c4] px-4 py-2 rounded">Создать</button>
                    </div>
                </form>
            </div>
        </div>
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
