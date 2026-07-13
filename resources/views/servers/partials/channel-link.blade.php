@php
    $isActive = isset($activeChannel) && $activeChannel && $activeChannel->id === $channel->id;
@endphp
<a href="{{ route('channels.show', [$channel->server_id, $channel]) }}"
   class="flex items-center px-2 py-1.5 rounded text-sm text-gray-300 hover:bg-[#3a3c42] hover:text-white transition-colors
          {{ $isActive ? 'bg-[#404249] text-white' : '' }}">
    <span class="mr-1.5 text-gray-500">{{ $channel->isVoice() ? '🔊' : '#' }}</span>
    <span class="truncate">{{ $channel->name }}</span>
</a>
