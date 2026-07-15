<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Server extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'icon_path', 'owner_id', 'invite_code'];

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
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=2B2D31&color=fff';
    }
}
