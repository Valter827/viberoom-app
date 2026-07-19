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
            $newPath = $request->file('avatar')->store('avatars', 'public');

            // 'public' диск сконфигурирован с 'throw' => false — при ошибке (например,
            // папка storage/app/public/avatars не создана или недоступна для записи)
            // store() вернёт false, ничего не бросая. Ловим это явно, чтобы не
            // записать в БД "false" вместо реального пути и не создать видимость
            // успешного сохранения при фактически битом аватаре.
            if ($newPath === false) {
                return back()->withErrors([
                    'avatar' => 'Не удалось сохранить файл на сервере. Обратитесь к администратору: '
                        . 'проверьте права на папку storage/app/public/avatars и что выполнена команда php artisan storage:link.',
                ]);
            }

            if ($user->avatar_path) {
                try {
                    Storage::disk('public')->delete($user->avatar_path);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $validated['avatar_path'] = $newPath;
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
