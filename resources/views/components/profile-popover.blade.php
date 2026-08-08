{{--
    Всплывающая карточка профиля пользователя (по образцу скриншота Discord).
    Слушает глобальное событие 'show-profile-popover' (см. openProfile() в app.js)
    и подгружает данные через /users/{id}/card.
--}}
<div
    x-data="{
        show: false,
        loading: false,
        user: null,
        x: 0,
        y: 0,
        async load(id) {
            this.loading = true;
            this.show = true;
            try {
                const res = await fetch(`/users/${id}/card`);
                this.user = await res.json();
            } finally {
                this.loading = false;
            }
        },
    }"
    x-on:show-profile-popover.window="
        x = $event.detail.x; y = $event.detail.y;
        load($event.detail.userId);
    "
    x-show="show"
    x-cloak
    @click.outside="show = false"
    @keydown.escape.window="show = false"
    class="fixed z-[100] w-72"
    :style="`left:${Math.min(x, window.innerWidth - 300)}px; top:${Math.min(y, window.innerHeight - 320)}px;`"
>
    <div class="rounded-xl overflow-hidden shadow-2xl bg-[#232428]" x-show="show" x-transition>

        <template x-if="loading || !user">
            <div class="p-6 flex items-center justify-center">
                <span class="text-xs text-gray-400">Загрузка…</span>
            </div>
        </template>

        <template x-if="!loading && user">
            <div>
                {{-- Баннер --}}
                <div class="h-16" :style="`background:${user.banner_color}`"></div>

                <div class="px-4 pb-4 -mt-8">
                    {{-- Аватар поверх баннера --}}
                    <div class="relative w-16 h-16 mb-2">
                        <img :src="user.avatar_url" class="w-16 h-16 rounded-full border-4 border-[#232428] object-cover">
                        <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full border-4 border-[#232428]"
                              :class="user.is_online ? 'bg-green-500' : 'bg-gray-500'"></span>
                    </div>

                    <div class="bg-[#111214] rounded-lg p-3">
                        <p class="text-white font-bold text-base" x-text="user.name"></p>
                        <p class="text-gray-400 text-xs" x-text="'@' + (user.username ?? '')"></p>

                        <template x-if="user.pronouns">
                            <p class="text-gray-400 text-xs mt-1" x-text="user.pronouns"></p>
                        </template>

                        <div class="border-t border-white/10 my-2"></div>

                        <template x-if="user.bio">
                            <div class="mb-2">
                                <p class="text-[11px] font-bold uppercase text-gray-400 mb-1">Обо мне</p>
                                <p class="text-sm text-gray-200 whitespace-pre-line" x-text="user.bio"></p>
                            </div>
                        </template>

                        <p class="text-[11px] font-bold uppercase text-gray-400 mb-1">На платформе с</p>
                        <p class="text-sm text-gray-200 mb-2" x-text="user.member_since"></p>

                        <template x-if="user.mutual_servers > 0">
                            <p class="text-xs text-gray-400 flex items-center gap-1"><x-icon name="link" class="w-3.5 h-3.5" /> Общих серверов: <span x-text="user.mutual_servers"></span></p>
                        </template>
                    </div>

                    {{-- Кнопка действия в зависимости от статуса дружбы --}}
                    <div class="mt-3">
                        <template x-if="user.relationship === 'none'">
                            <form method="POST" :action="'/friends'" @submit.prevent="
                                fetch('/friends', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ username: user.username }),
                                }).then(() => { user.relationship = 'outgoing'; })
                            ">
                                <button class="w-full bg-[#5865F2] hover:bg-[#4752c4] text-sm font-medium rounded py-2">
                                    Добавить в друзья
                                </button>
                            </form>
                        </template>
                        <template x-if="user.relationship === 'outgoing'">
                            <button disabled class="w-full bg-[#3a3c42] text-sm font-medium rounded py-2 text-gray-400">
                                Заявка отправлена
                            </button>
                        </template>
                        <template x-if="user.relationship === 'incoming'">
                            <a href="{{ route('friends.index') }}" class="block text-center w-full bg-emerald-600 hover:bg-emerald-500 text-sm font-medium rounded py-2">
                                Принять заявку
                            </a>
                        </template>
                        <template x-if="user.relationship === 'friends'">
                            <div class="w-full bg-[#2b2d31] text-sm font-medium rounded py-2 text-center text-emerald-400">
                                <x-icon name="check" class="w-3.5 h-3.5 inline -mt-0.5" /> В друзьях
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
