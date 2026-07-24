<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VibeActivity extends Model
{
    protected $fillable = ['user_id', 'category', 'label'];

    public const CATEGORIES = ['game', 'lfg', 'music'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toArray(): array
    {
        return [
            'category' => $this->category,
            'label' => $this->label,
        ];
    }
}
