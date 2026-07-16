<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceParticipant extends Model
{
    protected $fillable = ['channel_id', 'user_id', 'muted', 'last_seen_at'];
    protected $casts = ['muted' => 'boolean', 'last_seen_at' => 'datetime'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
