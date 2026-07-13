<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['channel_id', 'user_id', 'content', 'attachment_path', 'attachment_type'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentUrl(): ?string
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }
}
