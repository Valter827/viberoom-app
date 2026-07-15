@extends('layouts.app')

@section('content')
<div class="h-screen flex overflow-hidden">
    @include('servers.partials.server-sidebar')

    <main class="flex-1 flex flex-col bg-[#313338] overflow-y-auto">
        <div class="h-12 flex items-center px-4 border-b border-black/20 flex-shrink-0 gap-3">
            <a href="{{ route('servers.show', $server) }}" class="text-gray-400 hover:text-white text-sm">← Назад</a>
            <span class="font-semibold">Настройки сервера — {{ $server->name }}</span>
        </div>

        <div class="p-6 max-w-xl mx-auto w-full">

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/40 px-4 py-3">
                    <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                </div>
            @endif

            <div class="bg-[#2B2D31] rounded-lg p-6">
                <form method="POST" action="{{ route('servers.update', $server) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="flex items-center gap-4 mb-5">
                        <img src="{{ $server->iconUrl() }}" class="w-16 h-16 rounded-full object-cover">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Иконка сервера</label>
                            <input type="file" name="icon" accept="image/*" class="text-sm text-gray-300">
                        </div>
                    </div>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название сервера</label>
                    <input type="text" name="name" value="{{ old('name', $server->name) }}" required maxlength="100"
                           class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">
                    @error('name')
                        <p class="text-red-400 text-xs -mt-3 mb-3">{{ $message }}</p>
                    @enderror

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Описание сервера</label>
                    <textarea name="description" rows="3" maxlength="300"
                              class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4 resize-none"
                              placeholder="Расскажите, о чём этот сервер...">{{ old('description', $server->description) }}</textarea>

                    <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Код приглашения</label>
                    <div class="flex items-center gap-2 mb-5">
                        <input type="text" value="{{ $server->invite_code }}" readonly
                               class="flex-1 bg-[#1E1F22] rounded px-3 py-2 text-sm text-gray-400 outline-none">
                        <button type="button"
                                x-data
                                @click="navigator.clipboard.writeText('{{ $server->invite_code }}')"
                                class="text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-2 rounded">Копировать</button>
                    </div>

                    <button type="submit" class="w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                        Сохранить изменения
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
@endsection
