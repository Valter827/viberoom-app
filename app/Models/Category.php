<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['server_id', 'name', 'position'];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function channels()
    {
        return $this->hasMany(Channel::class)->orderBy('position');
    }
}
