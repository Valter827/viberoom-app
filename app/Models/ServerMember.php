<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Явная pivot-модель. Используется там, где нужно работать
 * со строкой "участник сервера" напрямую (например, смена роли,
 * бан/кик участника), а не только через belongsToMany.
 */
class ServerMember extends Model
{
    protected $fillable = ['server_id', 'user_id', 'role'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
