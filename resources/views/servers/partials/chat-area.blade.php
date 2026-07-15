{{-- Третья колонка: заголовок канала + лента сообщений + форма отправки --}}
<section class="flex-1 flex flex-col bg-[#313338] min-w-0"
    x-data="chatChannel({
        channelId: {{ $activeChannel?->id ?? 'null' }},
        currentUserId: {{ Auth::id() }},
        initialMessages: {{ $messages->map(fn($m) => [
            'id' => $m->id,
            'content' => $m->content,
            'attachment_url' => $m->attachmentUrl(),
            'attachment_type' => $m->attachment_type,
            'created_at' => $m->created_at->toIso8601String(),
            'user' => ['id' => $m->user->id, 'name' => $m->user->name, 'avatar_url' => $m->user->avatar_url],
        ])->toJson() }}
    })"
    x-init="init()">

    @if ($activeChannel)
        {{-- Шапка канала --}}
        <div class="h-12 flex items-center px-4 shadow-sm border-b border-black/20 flex-shrink-0">
            <span class="text-gray-400 mr-1.5">{{ $activeChannel->isVoice() ? '🔊' : '#' }}</span>
            <h2 class="font-semibold">{{ $activeChannel->name }}</h2>
        </div>

        {{-- Лента сообщений --}}
        <div class="flex-1 overflow-y-auto px-4 py-3" x-ref="messageList">
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex items-start gap-3 py-1.5 hover:bg-white/[0.02] px-2 -mx-2 rounded">
                    <img :src="msg.user.avatar_url" class="w-10 h-10 rounded-full flex-shrink-0 mt-0.5 cursor-pointer"
                         @click="openProfile(msg.user.id, $event)">
                    <div class="min-w-0">
                        <div class="flex items-baseline gap-2">
                            <span class="font-medium text-sm" x-text="msg.user.name"></span>
                            <span class="text-xs text-gray-500" x-text="formatTime(msg.created_at)"></span>
                        </div>
                        <p class="text-sm text-gray-200 break-words" x-text="msg.content" x-show="msg.content"></p>
                        <template x-if="msg.attachment_url && msg.attachment_type === 'image'">
                            <img :src="msg.attachment_url" class="mt-1 max-w-sm max-h-80 rounded-lg border border-black/20">
                        </template>
                        <template x-if="msg.attachment_url && msg.attachment_type === 'file'">
                            <a :href="msg.attachment_url" target="_blank"
                               class="mt-1 inline-block text-sm text-[#00a8fc] underline">📎 Скачать вложение</a>
                        </template>
                    </div>
                </div>
            </template>
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
                    placeholder="Написать в #{{ $activeChannel->name }}"
                    class="flex-1 bg-transparent outline-none text-sm placeholder-gray-500"
                    maxlength="4000">

                {{-- Простой набор эмодзи-кнопок; можно заменить на emoji-picker-element --}}
                <div class="relative ml-2" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="text-gray-400 hover:text-white">🙂</button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute bottom-8 right-0 bg-[#2B2D31] p-2 rounded-lg shadow-lg grid grid-cols-6 gap-1 z-10">
                        @foreach (['😀','😂','😍','👍','🔥','🎉','😢','😮','❤️','🙌','😎','🤔'] as $emoji)
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
    // Alpine-компонент чата: подписка на Echo + отправка сообщений через fetch()
    function chatChannel({ channelId, currentUserId, initialMessages }) {
        return {
            channelId,
            messages: initialMessages,
            content: '',
            attachment: null,

            init() {
                this.scrollToBottom();
                if (!this.channelId || !window.Echo) return;

                // Подписка на приватный канал вебсокетов channel.{id}
                window.Echo.private('channel.' + this.channelId)
                    .listen('.message.sent', (e) => {
                        this.messages.push(e);
                        this.$nextTick(() => this.scrollToBottom());
                    });
            },

            async send() {
                if (!this.content.trim() && !this.attachment) return;

                const formData = new FormData();
                if (this.content) formData.append('content', this.content);
                if (this.attachment) formData.append('attachment', this.attachment);

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
                    // добавляем своё сообщение локально сразу (toOthers() не шлёт его нам обратно)
                    this.messages.push(message);
                    this.content = '';
                    this.attachment = null;
                    this.$nextTick(() => this.scrollToBottom());
                }
            },

            scrollToBottom() {
                const el = this.$refs.messageList;
                if (el) el.scrollTop = el.scrollHeight;
            },

            formatTime(iso) {
                return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },
        };
    }
</script>
