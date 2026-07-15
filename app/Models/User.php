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
     * Пользователь онлайн, если его last_seen_at было недавно (например, < 60 секунд назад)
     * — используется как дополнение к явному статусу status.
     */
    public function isOnline(): bool
    {
        return $this->status !== 'offline'
            && $this->last_seen_at
            && $this->last_seen_at->diffInSeconds(now()) < 60;
    }
}
