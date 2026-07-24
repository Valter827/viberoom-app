@extends('layouts.app')

@section('content')
<div class="h-screen flex items-center justify-center">
    <div class="vr-card bg-[#313338] rounded-lg p-8 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-1">Создать сервер</h2>
        <p class="text-sm text-gray-400 mb-5">Твой сервер — твои правила. Начни с названия и иконки.</p>

        <form method="POST" action="{{ route('servers.store') }}" enctype="multipart/form-data"
              x-data="{ preview: null, name: '{{ old('name', '') }}' }">
            @csrf

            {{-- Иконка + превью в одной строке, как в реальном Discord-визарде создания сервера --}}
            <div class="flex items-center gap-4 mb-5">
                <label class="relative cursor-pointer group flex-shrink-0">
                    <div class="w-16 h-16 rounded-full bg-[#1E1F22] border-2 border-dashed border-gray-600 flex items-center justify-center overflow-hidden group-hover:border-[#5865F2] transition-colors">
                        <template x-if="preview">
                            <img :src="preview" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!preview">
                            <span class="text-2xl text-gray-500 group-hover:text-[#5865F2]">📷</span>
                        </template>
                    </div>
                    <input type="file" name="icon" accept="image/*" class="hidden"
                           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
                </label>
                <div class="text-xs text-gray-400">
                    <p class="font-medium text-gray-300 mb-0.5">Значок сервера</p>
                    <p>Необязательно. Рекомендуем изображение минимум 512×512.</p>
                </div>
            </div>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название сервера</label>
            <input type="text" name="name" required maxlength="100" x-model="name"
                   class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-1" placeholder="Например, «Команда мечты»">
            <p class="text-xs text-gray-500 mb-4 text-right" x-text="name.length + ' / 100'"></p>

            @error('name')
                <p class="text-red-400 text-xs mb-3">{{ $message }}</p>
            @enderror

            <button type="submit" :disabled="!name.trim()"
                    class="btn-lift w-full bg-[#5865F2] hover:bg-[#4752c4] disabled:opacity-50 disabled:cursor-not-allowed rounded py-2 text-sm font-medium">
                Создать
            </button>
        </form>
    </div>
</div>
@endsection
