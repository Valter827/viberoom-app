{{-- Настройки сервера в "оконном" режиме — открываются поверх текущей страницы
     и закрываются крестиком, как в Discord, вместо перехода на отдельную страницу. --}}
<div
    x-data="{ show: false, url: '' }"
    @open-server-settings.window="show = true; url = $event.detail.url"
    @keydown.escape.window="show = false"
    x-init="window.addEventListener('message', (e) => { if (e.data === 'close-server-settings') show = false; })"
>
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/70 z-[60] flex items-center justify-center p-6">
        <div class="bg-[#313338] rounded-lg overflow-hidden w-full h-full max-w-6xl relative shadow-2xl">
            <button @click="show = false"
                    class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center text-lg"
                    title="Закрыть (Esc)">
                ✕
            </button>
            <iframe :src="show ? url : ''" class="w-full h-full border-0" title="Настройки сервера"></iframe>
        </div>
    </div>
</div>
