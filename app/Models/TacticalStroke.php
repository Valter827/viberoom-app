<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TacticalStroke extends Model
{
    protected $fillable = ['tactical_board_id', 'user_id', 'version', 'tool', 'color', 'width', 'points'];

    public function board()
    {
        return $this->belongsTo(TacticalBoard::class, 'tactical_board_id');
    }

    public function toStrokeArray(): array
    {
        return [
            'id' => $this->id,
            'tool' => $this->tool,
            'color' => $this->color,
            'width' => $this->width,
            'points' => json_decode($this->points, true),
            'user_id' => $this->user_id,
        ];
    }
}
