@extends('layouts.app')

@section('content')
<div
    x-data="{
        name: {{ Js::from($user->name) }},
        bio: {{ Js::from($user->bio) }},
        pronouns: {{ Js::from($user->pronouns) }},
        bannerColor: {{ Js::from($user->banner_color) }},
        status: {{ Js::from($user->status) }},
        avatarPreview: {{ Js::from($user->avatar_url) }},
        onAvatarChange(e) {
            const file = e.target.files[0];
            if (file) this.avatarPreview = URL.createObjectURL(file);
        },
    }"
    class="h-screen flex overflow-hidden"
>
    @include('servers.partials.server-sidebar')

    <main class="flex-1 flex flex-col bg-[#313338] overflow-y-auto">
        <div class="h-12 flex items-center px-4 border-b border-black/20 flex-shrink-0 gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-white text-sm">← Назад</a>
            <span class="font-semibold">Настройки профиля</span>
        </div>

        <div class="p-6 max-w-4xl mx-auto w-full grid md:grid-cols-2 gap-6">

            {{-- Левая колонка: форма редактирования --}}
            <div class="bg-[#2B2D31] rounded-lg p-6">
                <h2 class="text-xs font-semibold uppercase text-gray-400 mb-4">Основной профиль</h2>

                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/40 px-4 py-3">
                        <p class="text-sm text-emerald-300">{{ session('status') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-500/10 border border-red-500/40 px-4 py-3">
                        <ul class="text-xs text-red-300 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    {{-- Аватар --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Аватар</label>
                        <div class="flex items-center gap-3">
                            <img :src="avatarPreview" class="w-16 h-16 rounded-full object-cover">
                            <label class="cursor-pointer text-xs bg-[#3a3c42] hover:bg-[#43454b] px-3 py-2 rounded">
                                Изменить
                                <input type="file" name="avatar" accept="image/*" class="hidden" @change="onAvatarChange">
                            </label>
                        </div>
                    </div>

                    {{-- Цвет баннера --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-2">Цвет баннера</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="banner_color" x-model="bannerColor" class="w-10 h-10 rounded cursor-pointer bg-transparent border-0 p-0">
                            <input type="text" x-model="bannerColor" readonly class="flex-1 bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none text-gray-300">
                        </div>
                    </div>

                    {{-- Отображаемое имя --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Никнейм</label>
                        <input type="text" name="name" x-model="name" required maxlength="50"
                               class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none">
                    </div>

                    {{-- Местоимения --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Местоимения</label>
                        <input type="text" name="pronouns" x-model="pronouns" maxlength="40" placeholder="Необязательно"
                               class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none">
                    </div>

                    {{-- О себе --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Обо мне</label>
                        <textarea name="bio" x-model="bio" rows="4" maxlength="190"
                                  placeholder="Расскажите немного о себе..."
                                  class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none resize-none"></textarea>
                        <p class="text-[11px] text-gray-500 mt-1 text-right" x-text="(bio?.length ?? 0) + ' / 190'"></p>
                    </div>

                    {{-- Статус --}}
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Статус</label>
                        <select name="status" x-model="status" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none">
                            @foreach (['online' => 'В сети', 'idle' => 'Нет на месте', 'dnd' => 'Не беспокоить', 'offline' => 'Невидимка'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                        Сохранить
                    </button>
                </form>
            </div>

            {{-- Правая колонка: живое превью карточки профиля --}}
            <div>
                <h2 class="text-xs font-semibold uppercase text-gray-400 mb-4">Предпросмотр</h2>
                <div class="rounded-xl overflow-hidden shadow-2xl bg-[#232428] w-72">
                    <div class="h-16" :style="`background:${bannerColor}`"></div>
                    <div class="px-4 pb-4 -mt-8">
                        <img :src="avatarPreview" class="w-16 h-16 rounded-full border-4 border-[#232428] object-cover mb-2">
                        <div class="bg-[#111214] rounded-lg p-3">
                            <p class="text-white font-bold text-base" x-text="name"></p>
                            <p class="text-gray-400 text-xs">{{ '@' . $user->username }}</p>
                            <p class="text-gray-400 text-xs mt-1" x-show="pronouns" x-text="pronouns"></p>
                            <div class="border-t border-white/10 my-2"></div>
                            <template x-if="bio">
                                <div>
                                    <p class="text-[11px] font-bold uppercase text-gray-400 mb-1">Обо мне</p>
                                    <p class="text-sm text-gray-200 whitespace-pre-line" x-text="bio"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 max-w-4xl mx-auto w-full pb-6">
            <h2 class="text-xs font-semibold uppercase text-gray-400 mb-3">Аудио, видео и демонстрация экрана</h2>
            @include('components.media-settings-panel')
        </div>
    </main>
</div>
@endsection
