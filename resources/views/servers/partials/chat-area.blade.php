{{-- Третья колонка: заголовок канала + лента сообщений + форма отправки --}}
<section class="flex-1 flex flex-col bg-[#313338] min-w-0"
    x-data="chatChannel({
        channelId: {{ $activeChannel?->id ?? 'null' }},
        currentUserId: {{ Auth::id() }},
        initialMessages: {{ $messages->map(fn($m) => $m->toChatArray(Auth::id()))->toJson() }}
    })"
    x-init="init()">

    @if ($activeChannel && $activeChannel->isVoice())
        @include('servers.partials.voice-channel')
    @elseif ($activeChannel)
        {{-- Шапка канала --}}
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0 gap-3">
            <span class="text-gray-400 mr-1.5">#</span>
            <h2 class="font-semibold flex-1 truncate">{{ $activeChannel->name }}</h2>

            <button @click="showPinned = !showPinned; if (showPinned) loadPinned()"
                    class="text-gray-400 hover:text-white text-sm" title="Закреплённые сообщения">📌</button>

            <div class="relative">
                <button @click="showSearch = !showSearch; $nextTick(() => showSearch && $refs.searchInput.focus())"
                        class="text-gray-400 hover:text-white text-sm" title="Поиск по сообщениям">🔍</button>
                <div x-show="showSearch" @click.outside="showSearch = false" x-cloak
                     class="absolute right-0 top-8 bg-[#1E1F22] rounded-lg shadow-xl p-2 w-72 z-20">
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

        {{-- Лента сообщений --}}
        <div class="flex-1 overflow-y-auto px-4 py-3" x-ref="messageList">
            <template x-for="msg in messages" :key="msg.id">
                <div>
                    {{-- Системное сообщение (вышел из сети / стал невидимым и т.п.) --}}
                    <template x-if="msg.is_system">
                        <div class="text-center py-1">
                            <span class="text-xs text-gray-500 italic" x-text="msg.content"></span>
                        </div>
                    </template>

                    {{-- Обычное сообщение --}}
                    <template x-if="!msg.is_system">
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
                            <span class="text-[10px] text-gray-500" x-show="msg.edited_at">(изменено)</span>
                            <span class="text-[10px] text-yellow-500" x-show="msg.pinned">📌 закреплено</span>
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
            <button @click="replyTo = null" class="text-gray-500 hover:text-white">✕</button>
        </div>

        {{-- Форма отправки сообщения --}}
        <div class="px-4 pb-6 pt-2 flex-shrink-0">
            <form @submit.prevent="send" class="relative flex items-center bg-[#383A40] rounded-lg px-3 py-2.5">
                <label class="cursor-pointer text-gray-400 hover:text-white mr-3" title="Прикрепить файл">
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
                    <button type="button" @click="open = !open" class="text-gray-400 hover:text-white">🙂</button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute bottom-8 right-0 bg-[#2B2D31] p-2 rounded-lg shadow-lg grid grid-cols-6 gap-1 z-10">
                        @foreach (["\u{1F600}","\u{1F602}","\u{1F60D}","\u{1F44D}","\u{1F525}","\u{1F389}","\u{1F622}","\u{1F62E}","\u{2764}\u{FE0F}","\u{1F64C}","\u{1F60E}","\u{1F914}"] as $emoji)
                            <button type="button" @click="content += '{{ $emoji }}'; open = false"
                                    class="text-lg hover:bg-white/10 rounded p-1">{{ $emoji }}</button>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="ml-2 text-[#5865F2] hover:text-white font-medium text-sm">Отправить</button>
            </form>
            <p class="text-xs text-gray-500 mt-1" x-show="attachment" x-text="attachment ? 'Прикреплено: ' + attachment.name : ''"></p>
        </div>
    @else
        <div class="flex-1 flex items-center justify-center text-gray-500">
            На этом сервере пока нет каналов.
        </div>
    @endif
</section>

<script>
    // Alpine-компонент чата: получение новых сообщений через polling + отправка через fetch()
    function chatChannel({ channelId, currentUserId, initialMessages }) {
        return {
            channelId,
            currentUserId,
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
            pollTimer: null,

            init() {
                this.scrollToBottom();
                if (!this.channelId) return;

                // Реал-тайм без вебсокета: раз в 3 секунды спрашиваем сервер,
                // нет ли новых сообщений — работает на любом хостинге без
                // постоянно запущенного процесса (Reverb и т.п. не нужны).
                this.pollTimer = setInterval(() => this.poll(), 3000);

                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) {
                        clearInterval(this.pollTimer);
                    } else {
                        this.poll();
                        this.pollTimer = setInterval(() => this.poll(), 3000);
                    }
                });
            },

            async poll() {
                try {
                    const res = await fetch(`/channels/${this.channelId}/messages/poll?after=${this.lastId}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const newMessages = await res.json();
                    if (!newMessages.length) return;

                    const byId = new Map(this.messages.map(m => [m.id, m]));
                    let scrolled = false;
                    for (const msg of newMessages) {
                        if (byId.has(msg.id)) {
                            // обновляем существующее (правка/пин/реакции)
                            Object.assign(byId.get(msg.id), msg);
                        } else {
                            this.messages.push(msg);
                            scrolled = true;
                        }
                        this.lastId = Math.max(this.lastId, msg.id);
                    }
                    if (scrolled) this.$nextTick(() => this.scrollToBottom());
                } catch (e) {
                    // сеть моргнула — попробуем на следующем тике
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

            // Экранируем HTML и подсвечиваем @упоминания, не давая внедрить произвольный HTML
            renderContent(text) {
                if (!text) return '';
                const escaped = text.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
                return escaped.replace(/@([a-zA-Z0-9_]{3,32})/g, '<span class="bg-[#5865F2]/30 text-[#c9cdfb] rounded px-1">@$1</span>');
            },
        };
    }
</script>
