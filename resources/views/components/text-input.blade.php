@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-md bg-[#1e1f22] border border-[#1e1f22] text-gray-100 focus:border-[#5865F2] focus:ring-0 transition-colors px-3 py-2.5']) }}>
