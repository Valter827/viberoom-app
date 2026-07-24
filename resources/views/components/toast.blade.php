{{-- Глобальные всплывающие уведомления, как в Discord.
     Показываются двумя способами:
     1) window.dispatchEvent(new CustomEvent('notify', { detail: 'Текст' }))
        или { detail: { message: 'Текст', type: 'error' } } — из любого места в JS/Alpine.
     2) Автоматически при редиректе с сервера, если контроллер положил
        session('status') (успех) или session('error') (ошибка). --}}
@php
    $flashError = session('error');
    $flashStatus = session('status');
@endphp
<div
    x-data="{
        toasts: [],
        push(message, type = 'success') {
            if (!message) return;
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    @notify.window="push($event.detail?.message ?? $event.detail, $event.detail?.type ?? 'success')"
    @if ($flashError || $flashStatus)
        x-init="push(@js($flashError ?: $flashStatus), '{{ $flashError ? 'error' : 'success' }}')"
    @endif
    class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto max-w-sm rounded-lg px-4 py-3 shadow-2xl text-sm font-medium flex items-center gap-2 text-white"
            :class="toast.type === 'error' ? 'bg-red-500/95' : 'bg-[#23a55a]/95'"
            @click="remove(toast.id)"
        >
            <span x-text="toast.type === 'error' ? '⚠️' : '✅'"></span>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
