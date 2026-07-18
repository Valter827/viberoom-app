<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Обновление ника, статуса и аватара пользователя.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:online,idle,dnd,offline'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:190'],
            'pronouns' => ['nullable', 'string', 'max:40'],
            'banner_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $previousStatus = $user->status;

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                try {
                    Storage::disk('public')->delete($user->avatar_path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        // 'avatar' (сам файл) не должен попадать в mass-assignment — только avatar_path
        unset($validated['avatar']);

        $user->update($validated);

        // если пользователь только что вручную поставил "невидимку" — сообщаем
        // об этом в каналах серверов (как при обычном выходе)
        if ($previousStatus !== 'offline' && $validated['status'] === 'offline') {
            $user->announceToServers($user->name . ' стал(а) невидимым(ой).');
        } elseif ($previousStatus === 'offline' && $validated['status'] !== 'offline') {
            $user->announceToServers($user->name . ' снова в сети.');
        }

        return back()->with('status', 'Профиль обновлён.');
    }
}
