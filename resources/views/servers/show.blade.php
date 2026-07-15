@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden">
    @include('servers.partials.server-sidebar')
    @include('servers.partials.channel-sidebar')
    @include('servers.partials.chat-area')

    {{-- Правая колонка со списком участников сервера (онлайн-статусы) --}}
    <aside class="w-60 flex-shrink-0 bg-[#2B2D31] p-3 overflow-y-auto hidden lg:block">
        <h3 class="text-xs font-semibold uppercase text-gray-400 mb-2">
            В сети — {{ $server->members->filter(fn($m) => $m->isOnline())->count() }}
        </h3>
        <div class="space-y-2">
            @foreach ($server->members as $member)
                <div class="flex items-center gap-2 px-1 py-1 rounded hover:bg-[#35373c] cursor-pointer"
                     onclick="openProfile({{ $member->id }}, event)">
                    <div class="relative">
                        <img src="{{ $member->avatar_url }}" class="w-8 h-8 rounded-full {{ $member->isOnline() ? '' : 'opacity-50' }}">
                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full border-2 border-[#2B2D31] {{ $member->isOnline() ? 'bg-green-500' : 'bg-gray-500' }}"></span>
                    </div>
                    <span class="text-sm truncate {{ $member->isOnline() ? 'text-gray-200' : 'text-gray-500' }}">{{ $member->name }}</span>
                </div>
            @endforeach
        </div>
    </aside>
</div>

{{-- Модалка присоединения к серверу по коду --}}
<div x-data="{ show: false }" @open-join-modal.window="show = true">
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div @click.outside="show = false" class="bg-[#313338] rounded-lg p-6 w-96">
            <h3 class="font-semibold mb-3">Присоединиться к серверу</h3>
            <form method="POST" action="{{ route('servers.join') }}">
                @csrf
                <input type="text" name="invite_code" placeholder="Введите код приглашения"
                       class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-3" required>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="show = false" class="text-sm text-gray-400 hover:text-white">Отмена</button>
                    <button type="submit" class="text-sm bg-[#5865F2] hover:bg-[#4752c4] px-4 py-2 rounded">Войти</button>
                </div>
            </form>
        </div>
    </div>
</div>
@include('components.profile-popover')
@endsection
