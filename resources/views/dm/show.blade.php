@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden">
    @include('servers.partials.server-sidebar')

    {{-- Вторая колонка: список личных чатов --}}
    <aside class="w-60 flex-shrink-0 bg-[#2B2D31] flex flex-col">
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
            <h1 class="font-semibold truncate">Личные сообщения</h1>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-3">
            <a href="{{ route('friends.index') }}"
               class="block px-2 py-2 rounded hover:bg-[#35373c] text-sm font-medium flex items-center gap-2 mb-3 text-gray-300 hover:text-white transition-colors">
                <span>🧑‍🤝‍🧑</span> Друзья
            </a>

            @if ($dmChannels->isNotEmpty())
                <h3 class="px-2 text-xs font-semibold uppercase text-gray-400 mb-1">Чаты</h3>
                <div class="space-y-0.5">
                    @foreach ($dmChannels as $dm)
                        @php $dmCompanion = $dm->otherParticipant(Auth::id()); @endphp
                        @if ($dmCompanion)
                            <a href="{{ route('dm.show', $dm) }}"
                               class="flex items-center gap-2 px-2 py-1.5 rounded text-sm transition-colors
                                      {{ $dm->id === $activeChannel->id ? 'bg-[#404249] text-white' : 'hover:bg-[#35373c] text-gray-300 hover:text-white' }}">
                                <div class="relative shrink-0">
                                    <img src="{{ $dmCompanion->avatar_url }}" class="w-7 h-7 rounded-full">
                                    <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full border-2 border-[#2B2D31] {{ $dmCompanion->isOnline() ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                                </div>
                                <span class="truncate">{{ $dmCompanion->name }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

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
            <button x-data @click="$dispatch('open-profile-settings')" class="icon-action icon-gear ml-auto text-sm" title="Настройки профиля">⚙️</button>
        </div>
    </aside>

    {{-- Лента сообщений — тот же компонент, что и в каналах серверов --}}
    @include('servers.partials.chat-area')
</div>

@include('components.profile-popover')
@include('components.profile-settings-modal')
@endsection
