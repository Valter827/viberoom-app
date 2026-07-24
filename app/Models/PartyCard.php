<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyCard extends Model
{
    protected $fillable = [
        'message_id', 'channel_id', 'creator_id', 'game', 'mode', 'max_slots', 'status',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function slots()
    {
        return $this->hasMany(PartyCardSlot::class)->orderBy('position');
    }

    /**
     * Формат карточки для фронтенда: слоты в фиксированном порядке,
     * пустые места — null.
     */
    public function toCardArray(): array
    {
        $slots = $this->slots()->with('user:id,name,avatar_path')->get()->keyBy('position');

        $seats = [];
        for ($i = 0; $i < $this->max_slots; $i++) {
            $slot = $slots->get($i);
            $seats[] = $slot ? [
                'position' => $i,
                'user_id' => $slot->user_id,
                'name' => $slot->user->name,
                'avatar_url' => $slot->user->avatar_url,
            ] : null;
        }

        return [
            'id' => $this->id,
            'game' => $this->game,
            'mode' => $this->mode,
            'max_slots' => $this->max_slots,
            'status' => $this->status,
            'creator_id' => $this->creator_id,
            'filled' => count(array_filter($seats)),
            'seats' => $seats,
        ];
    }
}
