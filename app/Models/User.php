<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'birthdate',
        'marketing_opt_in',
        'avatar_path',
        'status',
        'bio',
        'banner_color',
        'pronouns',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'birthdate' => 'date',
            'marketing_opt_in' => 'boolean',
        ];
    }

    /**
     * Серверы, которыми пользователь владеет.
     */
    public function ownedServers()
    {
        return $this->hasMany(Server::class, 'owner_id');
    }

    /**
     * Все серверы, в которых состоит пользователь (через server_members).
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Сообщения, отправленные пользователем.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Ссылка на аватар (с дефолтным значением, если аватар не загружен).
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar_path
            ? asset('storage/' . $this->avatar_path)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=5865F2&color=fff';
    }

    /**
     * Упоминания (@username) этого пользователя в сообщениях — для колокольчика уведомлений.
     */
    public function mentions()
    {
        return $this->hasMany(Mention::class);
    }

    /**
     * Пользователь онлайн, если его last_seen_at было недавно (например, < 60 секунд назад)
     * — используется как дополнение к явному статусу status.
     */
    public function isOnline(): bool
    {
        return $this->status !== 'offline'
            && $this->last_seen_at
            && $this->last_seen_at->diffInSeconds(now()) < 60;
    }

    /**
     * Заявки в друзья, отправленные этим пользователем.
     */
    public function outgoingFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    /**
     * Заявки в друзья, полученные этим пользователем.
     */
    public function incomingFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    /**
     * Список принятых друзей (в обе стороны связи).
     */
    public function friends()
    {
        $sent = $this->outgoingFriendRequests()->where('status', 'accepted')->with('recipient')->get()->pluck('recipient');
        $received = $this->incomingFriendRequests()->where('status', 'accepted')->with('requester')->get()->pluck('requester');

        return $sent->merge($received)->unique('id')->values();
    }

    /**
     * Входящие заявки, ожидающие решения (ещё не приняты).
     */
    public function pendingIncomingRequests()
    {
        return $this->incomingFriendRequests()->where('status', 'pending')->with('requester')->get();
    }

    /**
     * Исходящие заявки, ожидающие ответа от другого пользователя.
     */
    public function pendingOutgoingRequests()
    {
        return $this->outgoingFriendRequests()->where('status', 'pending')->with('recipient')->get();
    }

    /**
     * Текущий статус отношений с другим пользователем: none|friends|incoming|outgoing|self
     */
    public function relationshipStatusWith(User $other): string
    {
        if ($this->id === $other->id) {
            return 'self';
        }

        $outgoing = Friendship::where('user_id', $this->id)->where('friend_id', $other->id)->first();
        if ($outgoing) {
            return $outgoing->status === 'accepted' ? 'friends' : 'outgoing';
        }

        $incoming = Friendship::where('user_id', $other->id)->where('friend_id', $this->id)->first();
        if ($incoming) {
            return $incoming->status === 'accepted' ? 'friends' : 'incoming';
        }

        return 'none';
    }

    /**
     * Количество общих серверов с другим пользователем.
     */
    public function mutualServersCount(User $other): int
    {
        $myServerIds = $this->servers()->pluck('servers.id');

        return $other->servers()->whereIn('servers.id', $myServerIds)->count();
    }

    /**
     * Ссылка на баннер профиля (цвет, заданный пользователем).
     */
    public function getBannerColorAttribute($value): string
    {
        return $value ?: '#5865F2';
    }
}
