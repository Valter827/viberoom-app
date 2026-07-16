<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class MentionController extends Controller
{
    /**
     * Список последних упоминаний текущего пользователя (для колокольчика уведомлений).
     */
    public function index(): JsonResponse
    {
        $mentions = Auth::user()->mentions()
            ->with(['message.user:id,name,avatar_path', 'message.channel:id,name,server_id', 'message.channel.server:id,name'])
            ->whereHas('message') // на случай удалённого сообщения
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($mention) => [
                'id' => $mention->id,
                'read' => (bool) $mention->read_at,
                'created_at' => $mention->created_at->toIso8601String(),
                'message_content' => str($mention->message->content ?? '[вложение]')->limit(100)->value(),
                'from_user' => $mention->message->user->name,
                'server_id' => $mention->message->channel->server_id,
                'server_name' => $mention->message->channel->server->name,
                'channel_id' => $mention->message->channel_id,
                'channel_name' => $mention->message->channel->name,
            ]);

        return response()->json([
            'unread_count' => Auth::user()->mentions()->whereNull('read_at')->count(),
            'mentions' => $mentions,
        ]);
    }

    /**
     * Отметить все упоминания прочитанными.
     */
    public function markRead(): RedirectResponse|JsonResponse
    {
        Auth::user()->mentions()->whereNull('read_at')->update(['read_at' => now()]);

        return request()->wantsJson()
            ? response()->json(['ok' => true])
            : back();
    }
}
