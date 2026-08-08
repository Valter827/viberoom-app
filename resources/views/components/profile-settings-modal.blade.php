{{-- Настройки профиля в "оконном" режиме — открываются поверх текущей страницы
     и закрываются крестиком/Esc/кликом мимо, как в Discord, вместо перехода на отдельную страницу. --}}
<div
    x-data="{ show: false }"
    @open-profile-settings.window="show = true"
    @keydown.escape.window="show = false"
    x-init="window.addEventListener('message', (e) => { if (e.data === 'close-profile-settings') show = false; })"
>
    <div x-show="show" x-cloak class="fixed inset-0 bg-black/70 vr-backdrop z-[60] flex items-center justify-center p-6"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.self="show = false">
        <div class="bg-[#313338] rounded-lg overflow-hidden w-full h-full max-w-6xl relative shadow-2xl"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <button @click="show = false"
                    class="btn-lift absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center text-lg"
                    title="Закрыть (Esc)">
                <x-icon name="x" class="w-4 h-4" />
            </button>
            <iframe :src="show ? '{{ route('profile.edit') }}' : ''" class="w-full h-full border-0" title="Настройки профиля"></iframe>
        </div>
    </div>
</div>
