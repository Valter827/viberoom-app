<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = ['server_id', 'category_id', 'name', 'type', 'position'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isVoice(): bool
    {
        return $this->type === 'voice';
    }

    /**
     * Личный чат (DM) — канал без сервера, с двумя (пока) участниками через dm_participants.
     */
    public function isDm(): bool
    {
        return $this->type === 'dm';
    }

    /**
     * Участники личного чата.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'dm_participants')->withTimestamps();
    }

    /**
     * Собеседник в 1-на-1 личном чате (все, кроме указанного пользователя).
     */
    public function otherParticipant(int $userId): ?User
    {
        return $this->participants->firstWhere('id', '!=', $userId);
    }
}
