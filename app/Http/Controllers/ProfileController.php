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

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return back()->with('status', 'Профиль обновлён.');
    }
}
