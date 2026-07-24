<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TacticalBoard extends Model
{
    protected $fillable = ['channel_id', 'map_key', 'version'];

    public const MAPS = ['blank', 'dota', 'cs', 'valorant', 'rust'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function strokes()
    {
        return $this->hasMany(TacticalStroke::class);
    }
}
