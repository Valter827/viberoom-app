{{-- Колокольчик уведомлений об упоминаниях (@username). Работает через polling. --}}
<div
    x-data="{
        open: false,
        unread: 0,
        items: [],
        firstLoad: true,
        async load() {
            const res = await fetch('/mentions', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!this.firstLoad && data.unread_count > this.unread) {
                const mutedServers = JSON.parse(localStorage.getItem('muted_servers') || '[]');
                const latestServerId = data.mentions[0]?.server_id;
                if (!latestServerId || !mutedServers.includes(latestServerId)) {
                    window.Sounds?.mentionReceived();
                }
            }
            this.firstLoad = false;
            this.unread = data.unread_count;
            this.items = data.mentions;
        },
        async markRead() {
            await fetch('/mentions/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            });
            this.unread = 0;
        },
    }"
    x-init="load(); setInterval(() => load(), 15000)"
    class="relative"
>
    <button @click="open = !open; if (open) markRead()" class="relative text-gray-400 hover:text-white" title="Упоминания">
        <x-icon name="bell" class="w-4 h-4" />
        <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread"
              class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[10px] rounded-full min-w-[16px] h-4 flex items-center justify-center px-1"></span>
    </button>

    <div x-show="open" @click.outside="open = false" x-cloak
         class="absolute right-0 top-7 bg-[#1E1F22] rounded-lg shadow-xl w-80 max-h-96 overflow-y-auto z-30 p-2">
        <p class="text-xs font-semibold uppercase text-gray-400 px-2 py-1">Упоминания</p>
        <template x-if="!items.length">
            <p class="text-xs text-gray-500 px-2 py-3">Пока никто вас не упоминал.</p>
        </template>
        <template x-for="m in items" :key="m.id">
            <a :href="'/servers/' + m.server_id + '/channels/' + m.channel_id"
               class="block px-2 py-2 rounded hover:bg-white/5" :class="!m.read ? 'bg-white/[0.03]' : ''">
                <p class="text-xs text-gray-300">
                    <span class="font-medium" x-text="m.from_user"></span>
                    упомянул(а) вас в
                    <span class="text-gray-400" x-text="'#' + m.channel_name + ' · ' + m.server_name"></span>
                </p>
                <p class="text-xs text-gray-500 truncate mt-0.5" x-text="m.message_content"></p>
            </a>
        </template>
    </div>
</div>
