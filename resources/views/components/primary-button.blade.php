<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-lift inline-flex items-center px-4 py-2 bg-[#5865F2] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#4752c4] focus:outline-none focus:ring-2 focus:ring-[#5865F2] focus:ring-offset-2 focus:ring-offset-[#2b2d31]']) }}>
    {{ $slot }}
</button>
