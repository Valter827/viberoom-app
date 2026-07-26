<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FriendController extends Controller
{
    /**
     * Страница "Друзья": список друзей + входящие/исходящие заявки.
     */
    public function index(): View
    {
        $user = Auth::user();

        return view('friends.index', [
            'friends' => $user->friends(),
            'incoming' => $user->pendingIncomingRequests(),
            'outgoing' => $user->pendingOutgoingRequests(),
            'dmChannels' => $user->dmChannels()->with('participants')->get(),
        ]);
    }

    /**
     * Отправить заявку в друзья по нику (username).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $me = Auth::user();
        $target = User::where('username', ltrim($validated['username'], '@'))->first();

        if (! $target) {
            return back()->withErrors(['username' => 'Пользователь с таким ником не найден.']);
        }

        if ($target->id === $me->id) {
            return back()->withErrors(['username' => 'Нельзя добавить самого себя.']);
        }

        $status = $me->relationshipStatusWith($target);

        if ($status === 'friends') {
            return back()->withErrors(['username' => 'Вы уже друзья с этим пользователем.']);
        }

        if ($status === 'outgoing') {
            return back()->withErrors(['username' => 'Заявка уже отправлена.']);
        }

        // если у нас есть входящая заявка от этого пользователя — просто принимаем её
        if ($status === 'incoming') {
            Friendship::where('user_id', $target->id)->where('friend_id', $me->id)->update(['status' => 'accepted']);

            return back()->with('status', 'Вы теперь друзья с ' . $target->name . '!');
        }

        Friendship::create([
            'user_id' => $me->id,
            'friend_id' => $target->id,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Заявка в друзья отправлена: ' . $target->name);
    }

    /**
     * Принять входящую заявку.
     */
    public function accept(User $user): RedirectResponse
    {
        $me = Auth::user();

        Friendship::where('user_id', $user->id)
            ->where('friend_id', $me->id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);

        return back()->with('status', 'Заявка от ' . $user->name . ' принята.');
    }

    /**
     * Отклонить входящую заявку или отменить исходящую.
     */
    public function decline(User $user): RedirectResponse
    {
        $me = Auth::user();

        Friendship::where(function ($q) use ($me, $user) {
            $q->where('user_id', $me->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $me->id);
        })->where('status', 'pending')->delete();

        return back()->with('status', 'Заявка отклонена.');
    }

    /**
     * Удалить из друзей.
     */
    public function destroy(User $user): RedirectResponse
    {
        $me = Auth::user();

        Friendship::where(function ($q) use ($me, $user) {
            $q->where('user_id', $me->id)->where('friend_id', $user->id);
        })->orWhere(function ($q) use ($me, $user) {
            $q->where('user_id', $user->id)->where('friend_id', $me->id);
        })->delete();

        return back()->with('status', $user->name . ' удалён(а) из друзей.');
    }
}
