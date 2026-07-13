@extends('layouts.app')

@section('content')
<div class="h-screen flex items-center justify-center">
    <div class="bg-[#313338] rounded-lg p-8 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-5">Настройки профиля</h2>

        @if (session('status'))
            <p class="text-green-400 text-sm mb-4">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="flex items-center gap-3 mb-4">
                <img src="{{ $user->avatar_url }}" class="w-16 h-16 rounded-full">
                <input type="file" name="avatar" accept="image/*" class="text-sm">
            </div>

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Никнейм</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="50"
                   class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4">

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Статус</label>
            <select name="status" class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-5">
                @foreach (['online' => 'В сети', 'idle' => 'Нет на месте', 'dnd' => 'Не беспокоить', 'offline' => 'Невидимка'] as $value => $label)
                    <option value="{{ $value }}" @selected($user->status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                Сохранить
            </button>
        </form>
    </div>
</div>
@endsection
