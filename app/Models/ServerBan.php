<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerBan extends Model
{
    protected $fillable = ['server_id', 'user_id'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
