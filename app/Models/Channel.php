<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = ['server_id', 'category_id', 'name', 'type', 'position'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isVoice(): bool
    {
        return $this->type === 'voice';
    }
}
