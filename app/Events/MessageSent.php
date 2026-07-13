<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие рассылается всем участникам канала при отправке нового сообщения.
 * ShouldBroadcast — событие уходит через очередь в Reverb/Pusher/Soketi,
 * а не блокирует основной HTTP-запрос.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        // Подгружаем автора сообщения, чтобы не делать лишний запрос на фронте
        $this->message->load('user:id,name,avatar_path');
    }

    /**
     * Приватный канал вида "channel.{id}" — доступ проверяется
     * в routes/channels.php (пользователь должен быть участником сервера).
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel.' . $this->message->channel_id),
        ];
    }

    /**
     * Имя события на фронте (в Echo слушаем ".message.sent").
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Данные, которые реально уходят по вебсокету — не весь объект модели.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'attachment_url' => $this->message->attachmentUrl(),
            'attachment_type' => $this->message->attachment_type,
            'channel_id' => $this->message->channel_id,
            'created_at' => $this->message->created_at->toIso8601String(),
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar_url,
            ],
        ];
    }
}
