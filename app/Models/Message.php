<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    // при создании/изменении сообщения "трогаем" канал, чтобы список личных
    // чатов (dmChannels) можно было сортировать по последней активности
    protected $touches = ['channel'];

    protected $fillable = [
        'channel_id', 'user_id', 'parent_id', 'content', 'is_system', 'type',
        'attachment_path', 'attachment_type', 'edited_at', 'pinned_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'pinned_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function partyCard()
    {
        return $this->hasOne(PartyCard::class);
    }

    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }

    /**
     * Единый формат сообщения для фронтенда — используется и при первой
     * загрузке страницы (Blade), и в API-ответах (poll/store/search/pinned).
     */
    public function toChatArray(?int $viewerId = null): array
    {
        $viewerId ??= auth()->id();

        return [
            'id' => $this->id,
            'content' => $this->content,
            'attachment_url' => $this->attachmentUrl(),
            'attachment_type' => $this->attachment_type,
            'created_at' => $this->created_at->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'pinned' => (bool) $this->pinned_at,
            'is_system' => (bool) $this->is_system,
            'type' => $this->type ?? 'text',
            'party_card' => $this->type === 'party' ? $this->partyCard?->toCardArray() : null,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ],
            'parent' => $this->parent ? [
                'id' => $this->parent->id,
                'content' => str($this->parent->content ?? '[вложение]')->limit(80)->value(),
                'user_name' => $this->parent->user->name ?? '—',
            ] : null,
            'reactions' => $this->reactionsSummary($viewerId),
            'can_edit' => $this->user_id === $viewerId,
            'can_delete' => $this->user_id === $viewerId || ($this->channel->server?->canModerate($viewerId) ?? false),
            'can_pin' => $this->channel->server?->canModerate($viewerId) ?? false,
        ];
    }

    /**
     * Сгруппированные реакции для вывода в UI: [['emoji' => '👍', 'count' => 3, 'mine' => true], ...]
     */
    public function reactionsSummary(?int $currentUserId = null): array
    {
        return $this->reactions
            ->groupBy('emoji')
            ->map(fn ($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine' => $currentUserId ? $group->contains('user_id', $currentUserId) : false,
            ])
            ->values()
            ->all();
    }
}
