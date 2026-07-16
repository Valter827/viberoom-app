<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceSignal extends Model
{
    protected $fillable = ['channel_id', 'from_user_id', 'to_user_id', 'type', 'payload'];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
