<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'icon_path', 'owner_id', 'invite_code',
        'vibe_match_enabled', 'party_finder_enabled', 'tactical_canvas_enabled',
    ];

    protected $casts = [
        'vibe_match_enabled' => 'boolean',
        'party_finder_enabled' => 'boolean',
        'tactical_canvas_enabled' => 'boolean',
    ];

    /**
     * Автогенерация уникального invite_code при создании сервера,
     * если он не был передан явно.
     */
    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            if (empty($server->invite_code)) {
                $server->invite_code = Str::random(8);
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Все участники сервера (many-to-many через server_members).
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'server_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('position');
    }

    /**
     * Все каналы сервера (включая те, что без категории).
     */
    public function channels()
    {
        return $this->hasMany(Channel::class)->orderBy('position');
    }

    public function iconUrl(): string
    {
        return $this->icon_path
            ? asset('storage/' . $this->icon_path)
            : \App\Support\AvatarPlaceholder::dataUri($this->name, '#2B2D31');
    }

    public function bans()
    {
        return $this->hasMany(ServerBan::class);
    }

    /**
     * Роль пользователя на этом сервере (owner|admin|moderator|member) или null, если не участник.
     */
    public function roleOf(?int $userId): ?string
    {
        if (! $userId) {
            return null;
        }

        return $this->members()->where('user_id', $userId)->value('role');
    }

    /**
     * Может ли пользователь модерировать (удалять чужие сообщения, закреплять, кикать/банить member'ов).
     */
    public function canModerate(?int $userId): bool
    {
        return in_array($this->roleOf($userId), ['owner', 'admin', 'moderator'], true);
    }

    /**
     * Может ли пользователь управлять сервером (настройки, роли, баны) — только owner/admin.
     */
    public function canManage(?int $userId): bool
    {
        return in_array($this->roleOf($userId), ['owner', 'admin'], true);
    }
}
