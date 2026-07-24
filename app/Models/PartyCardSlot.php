<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyCardSlot extends Model
{
    protected $fillable = ['party_card_id', 'user_id', 'position'];

    public function partyCard()
    {
        return $this->belongsTo(PartyCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
