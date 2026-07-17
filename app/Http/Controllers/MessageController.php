<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Mention;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Опрос новых сообщений в канале (используется JS-поллингом как надёжная
     * замена вебсокетам — работает на любом хостинге без постоянного процесса).
     * Возвращает не только новые, но и недавно отредактированные/закреплённые
     * сообщения (клиент сам решает, обновить существующее или добавить новое).
     */
    public function poll(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $afterId = (int) $request->query('after', 0);

        $messages = $channel->messages()
            ->where(function ($q) use ($afterId) {
                $q->where('id', '>', $afterId)
                    // подтягиваем недавно отредактированные/закреплённые, чтобы обновить их на клиенте
                    ->orWhere('updated_at', '>=', now()->subMinutes(2));
            })
            ->with(['user:id,name,avatar_path', 'reactions.user:id,name', 'parent.user:id,name'])
            ->orderBy('id')
            ->limit(100)
            ->get();

        return response()->json($messages->map(fn ($m) => $m->toChatArray()));
    }

    /**
     * Отправить сообщение в канал (текст, вложение, ответ на другое сообщение).
     * Также парсит @упоминания и создаёт записи Mention для уведомлений.
     */
    public function store(Request $request, Channel $channel): JsonResponse
    {
        abort_unless($channel->isText(), 422, 'Сообщения можно отправлять только в текстовые каналы.');
        $this->authorizeMember($channel);

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:4000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240'], // до 10 МБ
            'parent_id' => ['nullable', 'integer', 'exists:messages,id'],
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
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $this->createMentions($message, $channel);

        $message->load(['user:id,name,avatar_path', 'reactions.user:id,name', 'parent.user:id,name']);

        // Реал-тайм для остальных участников идёт через polling (см. poll()),
        // поэтому broadcast() здесь больше не нужен — раньше попытка достучаться
        // до недоступного Reverb-сервера добавляла заметную задержку к каждой отправке.

        return response()->json($message->toChatArray(), 201);
    }

    /**
     * Редактировать своё сообщение.
     */
    public function update(Request $request, Message $message): JsonResponse
    {
        abort_unless($message->user_id === Auth::id(), 403, 'Можно редактировать только свои сообщения.');

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $message->update(['content' => $validated['content'], 'edited_at' => now()]);
        $this->createMentions($message, $message->channel);
        $message->load(['user:id,name,avatar_path', 'reactions.user:id,name', 'parent.user:id,name']);

        return response()->json($message->toChatArray());
    }

    /**
     * Удалить сообщение — сам автор, либо модератор/админ/владелец сервера.
     */
    public function destroy(Message $message): JsonResponse
    {
        $channel = $message->channel;
        $canModerate = $channel->server->canModerate(Auth::id());

        abort_unless($message->user_id === Auth::id() || $canModerate, 403, 'Недостаточно прав для удаления.');

        $message->delete();

        return response()->json(['deleted' => true, 'id' => $message->id]);
    }

    /**
     * Поставить/снять реакцию (toggle) на сообщение.
     */
    public function react(Request $request, Message $message): JsonResponse
    {
        $this->authorizeMember($message->channel);

        $validated = $request->validate([
            'emoji' => ['required', 'string', 'max:8'],
        ]);

        $existing = $message->reactions()
            ->where('user_id', Auth::id())
            ->where('emoji', $validated['emoji'])
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            $message->reactions()->create(['user_id' => Auth::id(), 'emoji' => $validated['emoji']]);
        }

        // реакции не меняют сами поля сообщения, поэтому "трогаем" его вручную,
        // чтобы polling() у других участников подхватил изменение
        $message->touch();

        $message->load('reactions.user:id,name');

        return response()->json(['reactions' => $message->reactionsSummary(Auth::id())]);
    }

    /**
     * Закрепить/открепить сообщение — только модератор/админ/владелец.
     */
    public function pin(Message $message): JsonResponse
    {
        abort_unless($message->channel->server->canModerate(Auth::id()), 403, 'Недостаточно прав.');

        $message->update(['pinned_at' => $message->pinned_at ? null : now()]);

        return response()->json(['pinned' => (bool) $message->pinned_at]);
    }

    /**
     * Список закреплённых сообщений канала.
     */
    public function pinned(Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $messages = $channel->messages()
            ->whereNotNull('pinned_at')
            ->with(['user:id,name,avatar_path'])
            ->orderByDesc('pinned_at')
            ->get();

        return response()->json($messages->map(fn ($m) => $m->toChatArray()));
    }

    /**
     * Поиск по сообщениям канала.
     */
    public function search(Request $request, Channel $channel): JsonResponse
    {
        $this->authorizeMember($channel);

        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        $messages = $channel->messages()
            ->where('content', 'like', '%' . $validated['q'] . '%')
            ->with(['user:id,name,avatar_path'])
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json($messages->map(fn ($m) => $m->toChatArray()));
    }

    private function authorizeMember(Channel $channel): void
    {
        abort_unless(
            $channel->server->members()->where('user_id', Auth::id())->exists(),
            403
        );
    }

    /**
     * Разобрать @username в тексте и создать записи Mention для найденных
     * пользователей-участников сервера (кроме самого автора, и без дублей).
     */
    private function createMentions(Message $message, Channel $channel): void
    {
        if (! $message->content) {
            return;
        }

        preg_match_all('/@([a-zA-Z0-9_]{3,32})/', $message->content, $matches);
        $usernames = array_unique($matches[1] ?? []);

        if (empty($usernames)) {
            return;
        }

        $mentionedUsers = User::whereIn('username', $usernames)
            ->whereIn('id', $channel->server->members()->pluck('users.id'))
            ->where('id', '!=', $message->user_id)
            ->get();

        foreach ($mentionedUsers as $user) {
            Mention::firstOrCreate([
                'user_id' => $user->id,
                'message_id' => $message->id,
            ]);
        }
    }

}
