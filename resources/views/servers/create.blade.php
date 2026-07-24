@extends('layouts.app')

@section('content')
<div class="h-screen flex items-center justify-center">
    <div class="vr-card bg-[#313338] rounded-lg p-8 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-1">Создать сервер</h2>
        <p class="text-sm text-gray-400 mb-5">Твой сервер — твои правила. Начни с названия и иконки.</p>

        <form method="POST" action="{{ route('servers.store') }}" enctype="multipart/form-data">
            @csrf
            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Название сервера</label>
            <input type="text" name="name" required maxlength="100"
                   class="w-full bg-[#1E1F22] rounded px-3 py-2 text-sm outline-none mb-4" placeholder="Например, «Команда мечты»">

            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1">Иконка (необязательно)</label>
            <input type="file" name="icon" accept="image/*" class="w-full text-sm mb-5">

            @error('name')
                <p class="text-red-400 text-xs mb-3">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-lift w-full bg-[#5865F2] hover:bg-[#4752c4] rounded py-2 text-sm font-medium">
                Создать
            </button>
        </form>
    </div>
</div>
@endsection
