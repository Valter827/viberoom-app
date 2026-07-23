<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-lift inline-flex items-center px-4 py-2 bg-[#3a3c42] border border-transparent rounded-md font-semibold text-xs text-gray-100 uppercase tracking-widest hover:bg-[#43454b] focus:outline-none focus:ring-2 focus:ring-[#5865F2] focus:ring-offset-2 focus:ring-offset-[#2b2d31] disabled:opacity-25']) }}>
    {{ $slot }}
</button>
