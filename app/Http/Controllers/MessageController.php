<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Отправить сообщение в канал. Возвращает JSON, т.к. отправка идёт
     * через fetch()/Alpine без перезагрузки страницы; сам вывод в чат у
     * ДРУГИХ пользователей происходит через broadcast-событие MessageSent.
     */
    public function store(Request $request, Channel $channel): JsonResponse
    {
        abort_unless($channel->isText(), 422, 'Сообщения можно отправлять только в текстовые каналы.');
        abort_unless(
            $channel->server->members()->where('user_id', Auth::id())->exists(),
            403
        );

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:4000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240'], // до 10 МБ
        ]);

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('attachments/' . $channel->id, 'public');
            $attachmentType = str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
        }

        $message = $channel->messages()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $message->load('user:id,name,avatar_path');

        // Рассылаем всем подписчикам приватного канала channel.{id} через Reverb
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
            'attachment_url' => $message->attachmentUrl(),
            'attachment_type' => $message->attachment_type,
            'created_at' => $message->created_at->toIso8601String(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'avatar_url' => $message->user->avatar_url,
            ],
        ], 201);
    }
}
