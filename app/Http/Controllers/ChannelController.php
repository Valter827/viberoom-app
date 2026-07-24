<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Server;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChannelController extends Controller
{
    /**
     * Показать сервер с открытым конкретным каналом (основной экран приложения).
     */
    public function show(Server $server, Channel $channel): View
    {
        abort_unless($channel->server_id === $server->id, 404);
        abort_unless($server->members()->where('user_id', Auth::id())->exists(), 403);

        $server->load(['categories.channels', 'channels' => fn ($q) => $q->whereNull('category_id')]);

        // подгружаем последние 50 сообщений канала в хронологическом порядке
        $messages = $channel->messages()
            ->with(['user:id,name,avatar_path', 'reactions.user:id,name', 'parent.user:id,name', 'partyCard.slots.user:id,name,avatar_path'])
            ->latest()->take(50)->get()->reverse()->values();

        return view('servers.show', [
            'server' => $server,
            'activeChannel' => $channel,
            'messages' => $messages,
        ]);
    }

    /**
     * Создать новый канал внутри сервера (текстовый или голосовой).
     */
    public function store(Request $request, Server $server): RedirectResponse
    {
        $role = $server->members()->where('user_id', Auth::id())->value('role');
        abort_unless(in_array($role, ['owner', 'admin']), 403, 'Недостаточно прав для создания канала.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:text,voice'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $channel = $server->channels()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name' => str_replace(' ', '-', strtolower($validated['name'])),
            'type' => $validated['type'],
            'position' => $server->channels()->count(),
        ]);

        return redirect()->route('channels.show', [$server, $channel]);
    }
}
