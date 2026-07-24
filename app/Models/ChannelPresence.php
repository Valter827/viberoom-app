<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPresence extends Model
{
    // В таблице нет created_at/updated_at — только last_seen_at,
    // поэтому отключаем автоматические таймстемпы Eloquent.
    public $timestamps = false;

    protected $fillable = ['channel_id', 'user_id', 'last_seen_at'];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
