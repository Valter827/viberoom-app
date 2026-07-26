<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    /**
     * Открыть личный чат с пользователем (создать, если его ещё нет) и перейти к нему.
     * Написать можно только тому, кто уже в друзьях.
     */
    public function store(User $user): RedirectResponse
    {
        $me = Auth::user();

        abort_if($user->id === $me->id, 422, 'Нельзя написать самому себе.');
        abort_unless($me->relationshipStatusWith($user) === 'friends', 403, 'Личные сообщения доступны только друзьям.');

        $channel = $this->findOrCreateDmChannel($me, $user);

        return redirect()->route('dm.show', $channel);
    }

    /**
     * Показать личный чат — переиспользуем ту же ленту сообщений/JS, что и в каналах серверов.
     */
    public function show(Channel $channel): View
    {
        abort_unless($channel->isDm(), 404);
        $this->authorizeParticipant($channel);

        $channel->load('participants');

        $messages = $channel->messages()->with([
            'user:id,name,avatar_path',
            'reactions.user:id,name',
            'parent.user:id,name',
        ])->latest()->limit(50)->get()->reverse()->values();

        $me = Auth::user();

        return view('dm.show', [
            'activeChannel' => $channel,
            'messages' => $messages,
            'companion' => $channel->otherParticipant($me->id),
            'dmChannels' => $me->dmChannels()->with('participants')->get(),
        ]);
    }

    /**
     * Найти существующий 1-на-1 DM-канал между двумя пользователями или создать новый.
     */
    private function findOrCreateDmChannel(User $a, User $b): Channel
    {
        $existing = Channel::query()
            ->where('type', 'dm')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $b->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        $channel = Channel::create([
            'server_id' => null,
            'category_id' => null,
            'name' => 'dm',
            'type' => 'dm',
        ]);

        $channel->participants()->attach([$a->id, $b->id]);

        return $channel;
    }

    private function authorizeParticipant(Channel $channel): void
    {
        abort_unless($channel->participants()->where('user_id', Auth::id())->exists(), 403);
    }
}
