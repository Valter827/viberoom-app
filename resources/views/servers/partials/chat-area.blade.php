{{-- Третья колонка: заголовок канала + лента сообщений + форма отправки --}}
<section class="flex-1 flex flex-col bg-[#313338] min-w-0 relative"
    x-data="chatChannel({
        channelId: {{ $activeChannel?->id ?? 'null' }},
        currentUserId: {{ Auth::id() }},
        initialMessages: {{ $messages->map(fn($m) => $m->toChatArray(Auth::id()))->toJson() }},
        vibeMatchEnabled: {{ $activeChannel && $activeChannel->server->vibe_match_enabled ? 'true' : 'false' }},
        partyFinderEnabled: {{ $activeChannel && $activeChannel->server->party_finder_enabled ? 'true' : 'false' }},
        tacticalCanvasEnabled: {{ $activeChannel && $activeChannel->server->tactical_canvas_enabled ? 'true' : 'false' }},
        canModerate: {{ $activeChannel && $activeChannel->server->canModerate(Auth::id()) ? 'true' : 'false' }}
    })"
    x-init="init()">

    @if ($activeChannel && $activeChannel->isVoice())
        @include('servers.partials.voice-channel')
    @elseif ($activeChannel)
        {{-- Шапка канала --}}
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0 gap-3 bg-[#313338]">
            <span class="w-6 h-6 rounded-full bg-white/5 flex items-center justify-center text-gray-400 text-sm font-semibold shrink-0">#</span>
            <h2 class="font-semibold flex-1 truncate">{{ $activeChannel->name }}</h2>

            <button @click="showPinned = !showPinned; if (showPinned) loadPinned()"
                    class="icon-action text-sm" title="Закреплённые сообщения">📌</button>

            <template x-if="vibeMatchEnabled">
                <div class="relative">
                    <button @click="showVibeForm = !showVibeForm; if (showVibeForm) loadMyActivity()"
                            class="icon-action text-sm" title="Что я сейчас делаю (Vibe Match)">🎯</button>
                    <div x-show="showVibeForm" @click.outside="showVibeForm = false" x-cloak
                         class="absolute right-0 top-8 bg-[#1E1F22] rounded-lg shadow-2xl p-3 w-72 z-20"
                         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        <p class="text-xs font-semibold uppercase text-gray-400 mb-2">Чем вы сейчас заняты?</p>
                        <div class="flex gap-1 mb-2">
                            <button type="button" @click="myActivity.category = 'game'"
                                    class="flex-1 text-xs py-1.5 rounded" :class="myActivity.category === 'game' ? 'bg-[#5865F2] text-white' : 'bg-[#2B2D31] text-gray-400'">🎮 Играю</button>
                            <button type="button" @click="myActivity.category = 'lfg'"
                                    class="flex-1 text-xs py-1.5 rounded" :class="myActivity.category === 'lfg' ? 'bg-[#5865F2] text-white' : 'bg-[#2B2D31] text-gray-400'">🔎 Ищу компанию</button>
                            <button type="button" @click="myActivity.category = 'music'"
                                    class="flex-1 text-xs py-1.5 rounded" :class="myActivity.category === 'music' ? 'bg-[#5865F2] text-white' : 'bg-[#2B2D31] text-gray-400'">🎧 Слушаю</button>
                        </div>
                        <input type="text" x-model="myActivity.label" maxlength="80"
                               placeholder="Например: Dota 2 или Interstellar OST"
                               class="w-full bg-[#2B2D31] rounded px-3 py-1.5 text-sm outline-none mb-2">
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="clearActivityStatus()" class="text-xs text-gray-400 hover:text-white px-2 py-1">Убрать статус</button>
                            <button type="button" @click="saveActivity()" class="text-xs bg-[#5865F2] hover:bg-[#4752c4] px-3 py-1.5 rounded font-medium">Сохранить</button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="partyFinderEnabled">
                <div class="relative">
                    <button @click="showPartyForm = !showPartyForm" class="icon-action text-sm" title="Создать карточку пати">🎮</button>
                    <div x-show="showPartyForm" @click.outside="showPartyForm = false" x-cloak
                         class="absolute right-0 top-8 bg-[#1E1F22] rounded-lg shadow-2xl p-3 w-72 z-20"
                         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        <p class="text-xs font-semibold uppercase text-gray-400 mb-2">Карточка пати</p>
                        <input type="text" x-model="partyForm.game" maxlength="60" placeholder="Игра (например, Dota 2)"
                               class="w-full bg-[#2B2D31] rounded px-3 py-1.5 text-sm outline-none mb-2">
                        <input type="text" x-model="partyForm.mode" maxlength="40" placeholder="Режим (например, Pos 4/5, Ranked)"
                               class="w-full bg-[#2B2D31] rounded px-3 py-1.5 text-sm outline-none mb-2">
                        <label class="text-xs text-gray-400 block mb-1">Слотов в команде: <span x-text="partyForm.max_slots"></span></label>
                        <input type="range" min="2" max="10" x-model.number="partyForm.max_slots" class="w-full mb-2">
                        <div class="flex justify-end">
                            <button type="button" @click="submitParty()" :disabled="!partyForm.game.trim()"
                                    class="text-xs bg-[#5865F2] hover:bg-[#4752c4] disabled:opacity-40 px-3 py-1.5 rounded font-medium">Создать</button>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="tacticalCanvasEnabled">
                <button @click="showTactical = !showTactical; showTactical ? openTactical() : closeTactical()"
                        class="icon-action text-sm" title="Тактический оверлей" :class="showTactical ? 'bg-[#5865F2]/30' : ''">🗺️</button>
            </template>


            <div class="relative">
                <button @click="showSearch = !showSearch; $nextTick(() => showSearch && $refs.searchInput.focus())"
                        class="icon-action text-sm" title="Поиск по сообщениям">🔍</button>
                <div x-show="showSearch" @click.outside="showSearch = false" x-cloak
                     class="absolute right-0 top-8 bg-[#1E1F22] rounded-lg shadow-2xl p-2 w-72 z-20"
                     x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <input x-ref="searchInput" type="text" x-model="searchQuery" @input.debounce.400ms="search()"
                           placeholder="Поиск в #{{ $activeChannel->name }}"
                           class="w-full bg-[#2B2D31] rounded px-3 py-1.5 text-sm outline-none mb-2">
                    <div class="max-h-72 overflow-y-auto space-y-1">
                        <template x-for="r in searchResults" :key="r.id">
                            <div class="text-xs bg-[#2B2D31] rounded p-2">
                                <span class="font-medium text-gray-300" x-text="r.user.name"></span>
                                <span class="text-gray-500 ml-1" x-text="formatTime(r.created_at)"></span>
                                <p class="text-gray-400 mt-0.5" x-text="r.content"></p>
                            </div>
                        </template>
                        <p class="text-xs text-gray-500" x-show="searchQuery.length > 1 && !searchResults.length">Ничего не найдено</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Панель закреплённых сообщений --}}
        <div x-show="showPinned" x-cloak class="border-b border-black/20 bg-[#2B2D31] max-h-48 overflow-y-auto px-4 py-2">
            <p class="text-xs font-semibold uppercase text-gray-400 mb-1">📌 Закреплённые сообщения</p>
            <template x-if="!pinnedMessages.length">
                <p class="text-xs text-gray-500">Пока нет закреплённых сообщений.</p>
            </template>
            <template x-for="p in pinnedMessages" :key="p.id">
                <div class="text-xs py-1 border-b border-white/5 last:border-0">
                    <span class="font-medium text-gray-300" x-text="p.user.name"></span>
                    <p class="text-gray-400" x-text="p.content"></p>
                </div>
            </template>
        </div>

        {{-- Плашки Vibe Match: живые пересечения интересов тех, кто сейчас в канале --}}
        <template x-if="vibeMatchEnabled && vibeMatches.length">
            <div class="px-4 pt-2 space-y-1.5 flex-shrink-0">
                <template x-for="m in vibeMatches" :key="m.key">
                    <div class="text-xs bg-gradient-to-r from-[#5865F2]/20 to-transparent border border-[#5865F2]/30 rounded-lg px-3 py-2 text-[#c9cdfb]">
                        <span x-text="m.text"></span>
                    </div>
                </template>
            </div>
        </template>

        {{-- Лента сообщений --}}
        <div class="flex-1 overflow-y-auto px-4 py-3" x-ref="messageList">
            <template x-for="(msg, msgIndex) in messages" :key="msg.id">
                <div>
                    {{-- Разделитель по дате: показывается перед первым сообщением дня --}}
                    <template x-if="msgIndex === 0 || formatDate(messages[msgIndex - 1].created_at) !== formatDate(msg.created_at)">
                        <div class="flex items-center gap-3 py-3 select-none">
                            <div class="flex-1 h-px bg-white/10"></div>
                            <span class="text-[11px] font-semibold text-gray-500" x-text="formatDate(msg.created_at)"></span>
                            <div class="flex-1 h-px bg-white/10"></div>
                        </div>
                    </template>

                    {{-- Системное сообщение (вышел из сети / стал невидимым и т.п.) --}}
                    <template x-if="msg.is_system">
                        <div class="text-center py-1">
                            <span class="text-xs text-gray-500 italic" x-text="msg.content"></span>
                        </div>
                    </template>

                    {{-- Карточка Пати: интерактивный виджет сбора команды --}}
                    <template x-if="!msg.is_system && msg.type === 'party'">
                        <div class="flex items-start gap-3 py-1.5 px-2 -mx-2">
                            <img :src="msg.user.avatar_url" class="w-10 h-10 rounded-full flex-shrink-0 mt-1 cursor-pointer" @click="openProfile(msg.user.id, $event)">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline gap-2 mb-1.5">
                                    <span class="font-medium text-sm" x-text="msg.user.name"></span>
                                    <span class="text-xs text-gray-500" x-text="formatTime(msg.created_at)"></span>
                                </div>
                                <div class="rounded-lg border overflow-hidden max-w-sm"
                                     :class="msg.party_card.status === 'full' ? 'border-emerald-500/50 bg-emerald-500/[0.06]' : (msg.party_card.status === 'cancelled' ? 'border-gray-600 bg-black/10 opacity-60' : 'border-[#5865F2]/40 bg-[#5865F2]/[0.06]')">
                                    <div class="px-3 py-2 flex items-center justify-between border-b border-white/5">
                                        <div>
                                            <p class="text-sm font-semibold" x-text="msg.party_card.game"></p>
                                            <p class="text-xs text-gray-400" x-show="msg.party_card.mode" x-text="msg.party_card.mode"></p>
                                        </div>
                                        <span class="text-xs px-2 py-0.5 rounded-full"
                                              :class="msg.party_card.status === 'full' ? 'bg-emerald-500/20 text-emerald-400' : (msg.party_card.status === 'cancelled' ? 'bg-gray-500/20 text-gray-400' : 'bg-[#5865F2]/20 text-[#c9cdfb]')"
                                              x-text="msg.party_card.status === 'full' ? 'Команда собрана' : (msg.party_card.status === 'cancelled' ? 'Отменено' : msg.party_card.filled + '/' + msg.party_card.max_slots)">
                                        </span>
                                    </div>
                                    <div class="p-2 grid grid-cols-5 gap-1.5">
                                        <template x-for="seat in msg.party_card.seats" :key="seat ? seat.position : Math.random()">
                                            <div>
                                                <template x-if="seat">
                                                    <div class="flex flex-col items-center gap-1 cursor-pointer group/seat" :title="seat.name"
                                                         @click="seat.user_id === currentUserId && leavePartySlot(msg)">
                                                        <img :src="seat.avatar_url" class="w-8 h-8 rounded-full" :class="seat.user_id === currentUserId ? 'ring-2 ring-[#5865F2] group-hover/seat:opacity-50' : ''">
                                                        <span class="text-[9px] text-gray-400 truncate w-full text-center" x-text="seat.name"></span>
                                                    </div>
                                                </template>
                                                <template x-if="!seat">
                                                    <button type="button" @click="joinPartySlot(msg)"
                                                            :disabled="msg.party_card.status !== 'open'"
                                                            class="w-8 h-8 rounded-full border-2 border-dashed border-gray-500 hover:border-[#5865F2] hover:text-[#5865F2] text-gray-500 flex items-center justify-center text-sm mx-auto disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    <template x-if="msg.party_card.status === 'open' && (msg.party_card.creator_id === currentUserId || canModerate)">
                                        <button type="button" @click="cancelParty(msg)" class="w-full text-center text-xs text-red-400 hover:bg-red-500/10 py-1.5 border-t border-white/5">Отменить карточку</button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Обычное сообщение --}}
                    <template x-if="!msg.is_system && msg.type !== 'party'">
                        <div class="group flex items-start gap-3 py-1.5 hover:bg-white/[0.02] px-2 -mx-2 rounded relative">

                            {{-- Плашка "в ответ на" --}}
                            <template x-if="msg.parent">
                                <div class="absolute -top-1 left-12 text-xs text-gray-500 flex items-center gap-1">
                                    <span>↪</span>
                                    <span class="font-medium" x-text="msg.parent.user_name"></span>:
                                    <span class="truncate max-w-xs" x-text="msg.parent.content"></span>
                                </div>
                            </template>

                    <img :src="msg.user.avatar_url" class="w-10 h-10 rounded-full flex-shrink-0 mt-3 cursor-pointer"
                         @click="openProfile(msg.user.id, $event)">
                    <div class="min-w-0 flex-1 mt-2">
                        <div class="flex items-baseline gap-2">
                            <span class="font-medium text-sm" x-text="msg.user.name"></span>
                            <span class="text-xs text-gray-500" x-text="formatTime(msg.created_at)"></span>
                            <span class="text-[10px] text-gray-500 bg-white/5 rounded px-1.5 py-0.5" x-show="msg.edited_at">изменено</span>
                            <span class="text-[10px] text-yellow-400 bg-yellow-400/10 rounded px-1.5 py-0.5 flex items-center gap-1" x-show="msg.pinned">📌 закреплено</span>
                        </div>

                        {{-- Обычный вид сообщения --}}
                        <template x-if="editingId !== msg.id">
                            <div>
                                <p class="text-sm text-gray-200 break-words" x-show="msg.content" x-html="renderContent(msg.content)"></p>
                                <template x-if="msg.attachment_url && msg.attachment_type === 'image'">
                                    <img :src="msg.attachment_url" class="mt-1 max-w-sm max-h-80 rounded-lg border border-black/20">
                                </template>
                                <template x-if="msg.attachment_url && msg.attachment_type === 'file'">
                                    <a :href="msg.attachment_url" target="_blank"
                                       class="mt-1 inline-block text-sm text-[#00a8fc] underline">📎 Скачать вложение</a>
                                </template>
                            </div>
                        </template>

                        {{-- Инлайн-редактирование --}}
                        <template x-if="editingId === msg.id">
                            <div class="flex gap-2 items-center">
                                <input type="text" x-model="editingContent" @keydown.enter="saveEdit(msg)" @keydown.escape="editingId = null"
                                       class="flex-1 bg-[#1E1F22] rounded px-2 py-1 text-sm outline-none">
                                <button @click="saveEdit(msg)" class="text-xs text-[#5865F2]">Сохранить</button>
                                <button @click="editingId = null" class="text-xs text-gray-400">Отмена</button>
                            </div>
                        </template>

                        {{-- Реакции --}}
                        <div class="flex gap-1 mt-1 flex-wrap" x-show="msg.reactions.length">
                            <template x-for="r in msg.reactions" :key="r.emoji">
                                <button @click="react(msg, r.emoji)"
                                        class="text-xs px-1.5 py-0.5 rounded-full border flex items-center gap-1"
                                        :class="r.mine ? 'bg-[#5865F2]/20 border-[#5865F2]' : 'bg-[#2B2D31] border-transparent hover:border-gray-500'">
                                    <span x-text="r.emoji"></span><span x-text="r.count"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Плавающая панель действий при наведении --}}
                    <div class="absolute right-2 -top-3 hidden group-hover:flex bg-[#2B2D31] rounded-lg shadow-lg border border-black/30 overflow-hidden">
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="px-2 py-1 hover:bg-white/10 text-sm" title="Реакция">🙂</button>
                            <div x-show="open" @click.outside="open = false" x-cloak
                                 class="absolute right-0 top-7 bg-[#1E1F22] p-1.5 rounded-lg shadow-xl grid grid-cols-6 gap-1 z-20 w-48">
                                <template x-for="e in ['\u{1F44D}','\u2764\uFE0F','\u{1F602}','\u{1F525}','\u{1F389}','\u{1F62E}','\u{1F622}','\u{1F64F}','\u{1F44F}','\u{1F60E}','\u{1F4AF}','\u2705']" :key="e">
                                    <button @click="react(msg, e); open = false" class="text-lg hover:bg-white/10 rounded p-1" x-text="e"></button>
                                </template>
                            </div>
                        </div>
                        <button @click="replyTo = msg" class="px-2 py-1 hover:bg-white/10 text-sm" title="Ответить">↩️</button>
                        <template x-if="msg.can_edit">
                            <button @click="editingId = msg.id; editingContent = msg.content" class="px-2 py-1 hover:bg-white/10 text-sm" title="Редактировать">✏️</button>
                        </template>
                        <template x-if="msg.can_pin">
                            <button @click="togglePin(msg)" class="px-2 py-1 hover:bg-white/10 text-sm" title="Закрепить">📌</button>
                        </template>
                        <template x-if="msg.can_delete">
                            <button @click="deleteMessage(msg)" class="px-2 py-1 hover:bg-white/10 text-sm text-red-400" title="Удалить">🗑️</button>
                        </template>
                    </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Плашка "ответ на сообщение" над полем ввода --}}
        <div x-show="replyTo" x-cloak class="px-4 flex items-center gap-2 text-xs text-gray-400 flex-shrink-0">
            <span>↪ Ответ пользователю <span class="font-medium" x-text="replyTo?.user?.name"></span></span>
            <button @click="replyTo = null" class="icon-action !w-5 !h-5 text-xs">✕</button>
        </div>

        {{-- Форма отправки сообщения --}}
        <div class="px-4 pb-6 pt-2 flex-shrink-0">
            <form @submit.prevent="send" class="relative flex items-center bg-[#383A40] rounded-xl px-3 py-2.5 shadow-inner transition-shadow focus-within:ring-2 focus-within:ring-[#5865F2]/60">
                <label class="icon-action mr-3" title="Прикрепить файл">
                    📎
                    <input type="file" class="hidden" @change="attachment = $event.target.files[0]">
                </label>

                <input
                    type="text"
                    x-model="content"
                    placeholder="Написать в #{{ $activeChannel->name }} (@ для упоминания)"
                    class="flex-1 bg-transparent outline-none text-sm placeholder-gray-500"
                    maxlength="4000">

                {{-- Простой набор эмодзи-кнопок --}}
                <div class="relative ml-2" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="icon-action">🙂</button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute bottom-8 right-0 bg-[#2B2D31] p-2 rounded-lg shadow-2xl grid grid-cols-6 gap-1 z-10"
                         x-transition:enter="ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                        @foreach (["\u{1F600}","\u{1F602}","\u{1F60D}","\u{1F44D}","\u{1F525}","\u{1F389}","\u{1F622}","\u{1F62E}","\u{2764}\u{FE0F}","\u{1F64C}","\u{1F60E}","\u{1F914}"] as $emoji)
                            <button type="button" @click="content += '{{ $emoji }}'; open = false"
                                    class="text-lg hover:bg-white/10 rounded p-1">{{ $emoji }}</button>
                        @endforeach
                    </div>
                </div>

                <button type="submit"
                        :disabled="!content.trim() && !attachment"
                        title="Отправить"
                        class="btn-lift ml-2 w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 enabled:text-[#5865F2] enabled:hover:text-white enabled:hover:bg-[#5865F2] disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.4 20.6l17.45-8.32a.75.75 0 000-1.36L3.4 2.6a.75.75 0 00-1.06.85l1.9 7.13a.75.75 0 00.58.55l8.3 1.37-8.3 1.37a.75.75 0 00-.58.55l-1.9 7.13a.75.75 0 001.06.85z"/></svg>
                </button>
            </form>
            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5" x-show="attachment">
                📎 <span x-text="attachment ? 'Прикреплено: ' + attachment.name : ''"></span>
                <button type="button" @click="attachment = null" class="text-gray-500 hover:text-red-400 ml-1">✕</button>
            </p>
        </div>

        @if ($activeChannel->server->tactical_canvas_enabled)
            @include('servers.partials.tactical-canvas')
        @endif
    @else
        <div class="flex-1 flex flex-col items-center justify-center text-center px-6">
            <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center text-3xl mb-4">💬</div>
            <p class="text-gray-300 font-medium">На этом сервере пока нет каналов</p>
            <p class="text-sm text-gray-500 mt-1 max-w-xs">Создайте текстовый или голосовой канал, чтобы начать общение с участниками.</p>
        </div>
    @endif
</section>

<script>
    // Alpine-компонент чата: получение новых сообщений через polling + отправка через fetch()
    function chatChannel({ channelId, currentUserId, initialMessages, vibeMatchEnabled, partyFinderEnabled, tacticalCanvasEnabled, canModerate }) {
        return {
            channelId,
            currentUserId,
            vibeMatchEnabled,
            partyFinderEnabled,
            tacticalCanvasEnabled,
            canModerate,
            messages: initialMessages,
            content: '',
            attachment: null,
            replyTo: null,
            editingId: null,
            editingContent: '',
            showPinned: false,
            pinnedMessages: [],
            showSearch: false,
            searchQuery: '',
            searchResults: [],
            lastId: initialMessages.length ? Math.max(...initialMessages.map(m => m.id)) : 0,
            polling: false,
            pollTimer: null,

            // --- Vibe Match ---
            showVibeForm: false,
            myActivity: { category: 'game', label: '' },
            vibeMatches: [],
            vibeTimer: null,

            // --- Party Finder ---
            showPartyForm: false,
            partyForm: { game: '', mode: '', max_slots: 5 },

            // --- Tactical Canvas ---
            showTactical: false,
            tacticalMapKey: 'blank',
            tacticalVersion: 1,
            tacticalStrokes: [],
            tacticalTool: 'pen',
            tacticalColor: '#f23f42',
            tacticalWidth: 3,
            tacticalLastId: 0,
            tacticalPollTimer: null,
            tacticalDrawing: false,
            tacticalCurrentPoints: [],

            init() {
                this.scrollToBottom();
                if (!this.channelId) return;

                // Реал-тайм без вебсокета: раз в 3 секунды спрашиваем сервер,
                // нет ли новых сообщений — работает на любом хостинге без
                // постоянно запущенного процесса (Reverb и т.п. не нужны).
                this.pollTimer = setInterval(() => this.poll(), 1000);

                if (this.vibeMatchEnabled) {
                    this.vibeHeartbeat();
                    this.vibeTimer = setInterval(() => this.vibeHeartbeat(), 5000);
                }

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        clearInterval(this.pollTimer);
                        clearInterval(this.vibeTimer);
                    } else {
                        this.poll();
                        this.pollTimer = setInterval(() => this.poll(), 1000);
                        if (this.vibeMatchEnabled) {
                            this.vibeHeartbeat();
                            this.vibeTimer = setInterval(() => this.vibeHeartbeat(), 5000);
                        }
                    }
                });
            },

            // --- Vibe Match: методы ---
            async vibeHeartbeat() {
                try {
                    const res = await fetch(`/channels/${this.channelId}/vibe/heartbeat`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.vibeMatches = data.matches;
                    }
                } catch (e) { /* сеть моргнула — попробуем в следующий раз */ }
            },

            async loadMyActivity() {
                const res = await fetch('/vibe/activity', { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const data = await res.json();
                    if (data.activity) this.myActivity = data.activity;
                }
            },

            async saveActivity() {
                if (!this.myActivity.label.trim()) return;
                const res = await fetch('/vibe/activity', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.myActivity),
                });
                if (res.ok) {
                    this.showVibeForm = false;
                    this.vibeHeartbeat();
                }
            },

            async clearActivityStatus() {
                await fetch('/vibe/activity', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                this.myActivity = { category: 'game', label: '' };
                this.showVibeForm = false;
                this.vibeHeartbeat();
            },

            // --- Party Finder: методы ---
            async submitParty() {
                if (!this.partyForm.game.trim()) return;
                const res = await fetch(`/channels/${this.channelId}/party-cards`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.partyForm),
                });
                if (res.ok) {
                    const message = await res.json();
                    this.messages.push(message);
                    this.lastId = Math.max(this.lastId, message.id);
                    this.showPartyForm = false;
                    this.partyForm = { game: '', mode: '', max_slots: 5 };
                    this.$nextTick(() => this.scrollToBottom());
                }
            },

            async joinPartySlot(msg) {
                const freePosition = msg.party_card.seats.findIndex(s => !s);
                if (freePosition === -1) return;
                const res = await fetch(`/party-cards/${msg.party_card.id}/join`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ position: freePosition }),
                });
                if (res.ok) {
                    const card = await res.json();
                    const wasOpen = msg.party_card.status !== 'full';
                    msg.party_card = card;
                    if (wasOpen && card.status === 'full') window.Sounds?.messageReceived();
                } else if (res.status === 409) {
                    // кто-то занял слот на долю секунды раньше — подождём ближайший poll
                }
            },

            async leavePartySlot(msg) {
                const res = await fetch(`/party-cards/${msg.party_card.id}/leave`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) msg.party_card = await res.json();
            },

            async cancelParty(msg) {
                if (!confirm('Отменить карточку пати?')) return;
                const res = await fetch(`/party-cards/${msg.party_card.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) msg.party_card = await res.json();
            },

            // --- Tactical Canvas: методы ---
            async openTactical() {
                const res = await fetch(`/channels/${this.channelId}/tactical`, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.tacticalMapKey = data.map_key;
                this.tacticalVersion = data.version;
                this.tacticalStrokes = data.strokes;
                this.tacticalLastId = data.strokes.length ? Math.max(...data.strokes.map(s => s.id)) : 0;
                this.tacticalPollTimer = setInterval(() => this.tacticalPoll(), 1000);
            },

            closeTactical() {
                clearInterval(this.tacticalPollTimer);
            },

            async tacticalPoll() {
                const res = await fetch(`/channels/${this.channelId}/tactical/poll?after=${this.tacticalLastId}&version=${this.tacticalVersion}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data.reset || data.version !== this.tacticalVersion) {
                    this.tacticalVersion = data.version;
                    this.tacticalMapKey = data.map_key;
                    this.tacticalStrokes = data.strokes;
                } else if (data.strokes.length) {
                    this.tacticalStrokes.push(...data.strokes);
                }
                if (data.strokes.length) this.tacticalLastId = Math.max(this.tacticalLastId, ...data.strokes.map(s => s.id));
            },

            async tacticalSetMap(key) {
                this.tacticalMapKey = key;
                const res = await fetch(`/channels/${this.channelId}/tactical/map`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ map_key: key }),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.tacticalVersion = data.version;
                    this.tacticalStrokes = [];
                    this.tacticalLastId = 0;
                }
            },

            async tacticalClear() {
                const res = await fetch(`/channels/${this.channelId}/tactical/clear`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    this.tacticalVersion = data.version;
                    this.tacticalStrokes = [];
                    this.tacticalLastId = 0;
                }
            },

            tacticalPointFromEvent(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                const point = e.touches ? e.touches[0] : e;
                return {
                    x: Math.max(0, Math.min(100, ((point.clientX - rect.left) / rect.width) * 100)),
                    y: Math.max(0, Math.min(100, ((point.clientY - rect.top) / rect.height) * 100)),
                };
            },

            tacticalPointerDown(e) {
                this.tacticalDrawing = true;
                this.tacticalCurrentPoints = [this.tacticalPointFromEvent(e)];
            },

            tacticalPointerMove(e) {
                if (!this.tacticalDrawing) return;
                const p = this.tacticalPointFromEvent(e);
                if (this.tacticalTool === 'pen') {
                    this.tacticalCurrentPoints.push(p);
                } else {
                    // line/arrow — нам нужны только начало и конец
                    this.tacticalCurrentPoints = [this.tacticalCurrentPoints[0], p];
                }
            },

            async tacticalPointerUp() {
                if (!this.tacticalDrawing) return;
                this.tacticalDrawing = false;
                if (this.tacticalCurrentPoints.length < 2) { this.tacticalCurrentPoints = []; return; }

                const stroke = {
                    tool: this.tacticalTool,
                    color: this.tacticalColor,
                    width: this.tacticalWidth,
                    points: this.tacticalCurrentPoints,
                };
                this.tacticalCurrentPoints = [];

                const res = await fetch(`/channels/${this.channelId}/tactical/strokes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(stroke),
                });
                if (res.ok) {
                    const saved = await res.json();
                    this.tacticalStrokes.push(saved);
                    this.tacticalLastId = Math.max(this.tacticalLastId, saved.id);
                }
            },

            tacticalPolylinePoints(stroke) {
                return stroke.points.map(p => `${p.x},${p.y}`).join(' ');
            },

            // Строим разметку штрихов строкой (а не через x-for) — Alpine ломает
            // scope переменной цикла внутри <svg>, см. комментарий в blade-файле.
            renderTacticalStrokes() {
                const esc = (v) => String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                const polyline = (points, color, width, tool, opacity) => {
                    const marker = tool === 'arrow' ? ' marker-end="url(#arrowhead)"' : '';
                    const op = opacity ? ` opacity="${opacity}"` : '';
                    return `<polyline points="${esc(points)}" fill="none" stroke="${esc(color)}" stroke-width="${Number(width) * 0.35}" stroke-linecap="round" stroke-linejoin="round"${marker}${op}></polyline>`;
                };

                let svg = this.tacticalStrokes
                    .map(s => polyline(this.tacticalPolylinePoints(s), s.color, s.width, s.tool))
                    .join('');

                if (this.tacticalDrawing && this.tacticalCurrentPoints.length > 1) {
                    const points = this.tacticalCurrentPoints.map(p => `${p.x},${p.y}`).join(' ');
                    svg += polyline(points, this.tacticalColor, this.tacticalWidth, this.tacticalTool, 0.85);
                }

                return svg;
            },

            async poll() {
                if (this.polling) return; // не даём запросам копиться друг на друга при 1-сек интервале
                this.polling = true;
                try {
                    const res = await fetch(`/channels/${this.channelId}/messages/poll?after=${this.lastId}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const newMessages = await res.json();
                    if (!newMessages.length) return;

                    const byId = new Map(this.messages.map(m => [m.id, m]));
                    let scrolled = false;
                    let playSound = false;
                    for (const msg of newMessages) {
                        if (byId.has(msg.id)) {
                            const existing = byId.get(msg.id);
                            // "команда собрана" — проигрываем звук, если карточка пати только что заполнилась
                            if (msg.type === 'party' && existing.party_card && msg.party_card
                                && existing.party_card.status !== 'full' && msg.party_card.status === 'full') {
                                playSound = true;
                            }
                            // обновляем существующее (правка/пин/реакции/слоты пати)
                            Object.assign(existing, msg);
                        } else {
                            this.messages.push(msg);
                            scrolled = true;
                            if (!msg.is_system && msg.user.id !== this.currentUserId) {
                                playSound = true;
                            }
                        }
                        this.lastId = Math.max(this.lastId, msg.id);
                    }
                    if (scrolled) this.$nextTick(() => this.scrollToBottom());
                    if (playSound) window.Sounds?.messageReceived();
                } catch (e) {
                    // сеть моргнула — попробуем на следующем тике
                } finally {
                    this.polling = false;
                }
            },

            async send() {
                if (!this.content.trim() && !this.attachment) return;

                const formData = new FormData();
                if (this.content) formData.append('content', this.content);
                if (this.attachment) formData.append('attachment', this.attachment);
                if (this.replyTo) formData.append('parent_id', this.replyTo.id);

                const res = await fetch(`/channels/${this.channelId}/messages`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (res.ok) {
                    const message = await res.json();
                    this.messages.push(message);
                    this.lastId = Math.max(this.lastId, message.id);
                    this.content = '';
                    this.attachment = null;
                    this.replyTo = null;
                    this.$nextTick(() => this.scrollToBottom());
                } else {
                    alert('Не удалось отправить сообщение. Попробуйте ещё раз.');
                }
            },

            async saveEdit(msg) {
                if (!this.editingContent.trim()) return;
                const res = await fetch(`/messages/${msg.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ content: this.editingContent }),
                });
                if (res.ok) {
                    const updated = await res.json();
                    Object.assign(msg, updated);
                    this.editingId = null;
                }
            },

            async deleteMessage(msg) {
                if (!confirm('Удалить это сообщение?')) return;
                const res = await fetch(`/messages/${msg.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    this.messages = this.messages.filter(m => m.id !== msg.id);
                }
            },

            async react(msg, emoji) {
                const res = await fetch(`/messages/${msg.id}/react`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ emoji }),
                });
                if (res.ok) {
                    const data = await res.json();
                    msg.reactions = data.reactions;
                }
            },

            async togglePin(msg) {
                const res = await fetch(`/messages/${msg.id}/pin`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    msg.pinned = data.pinned;
                }
            },

            async loadPinned() {
                const res = await fetch(`/channels/${this.channelId}/messages/pinned`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.pinnedMessages = await res.json();
            },

            async search() {
                if (this.searchQuery.length < 2) { this.searchResults = []; return; }
                const res = await fetch(`/channels/${this.channelId}/messages/search?q=${encodeURIComponent(this.searchQuery)}`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) this.searchResults = await res.json();
            },

            scrollToBottom() {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            },

            formatTime(iso) {
                return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            // Человекочитаемая дата для разделителей ленты: "Сегодня" / "Вчера" / "25 июля 2026"
            formatDate(iso) {
                const d = new Date(iso);
                const now = new Date();
                const startOf = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
                const diffDays = Math.round((startOf(now) - startOf(d)) / 86400000);
                if (diffDays === 0) return 'Сегодня';
                if (diffDays === 1) return 'Вчера';
                return d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
            },

            // Экранируем HTML и подсвечиваем @упоминания, не давая внедрить произвольный HTML
            renderContent(text) {
                if (!text) return '';
                const escaped = text.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                return escaped.replace(/@([a-zA-Z0-9_]{3,32})/g, '<span class="bg-[#5865F2]/30 text-[#c9cdfb] rounded px-1">@$1</span>');
            },
        };
    }
</script>
